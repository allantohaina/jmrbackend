<?php

namespace App\Controllers\Client;

use App\Models\DocumentModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Config\Documents;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Throwable;

class DocumentController extends ResourceController
{
    protected $format = 'json';

    private const ALLOWED_TYPES = ['preuve_paiement', 'devis', 'recu'];

    /**
     * Upload en FLUX BRUT (php://input) : contourne le bug CageFS
     * (upload_tmp_dir bloqué sur /tmp, getError()=6 sur tout $_FILES).
     * Le corps de la requête EST le fichier ; les métadonnées passent en
     * query string / headers. Contrat de réponse JSON inchangé.
     */
    public function upload(): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $userId = $actor['id'] ?? null;
            if (!$userId) {
                return $this->failUnauthorized('Authentification requise.');
            }

            // Métadonnées en query string / headers, pas dans le body (occupé par le fichier).
            $type = strtolower((string) ($this->request->getGet('type') ?? 'preuve_paiement'));
            if (!in_array($type, self::ALLOWED_TYPES, true)) {
                return $this->fail(['type' => 'Type de document invalide (preuve_paiement, devis, recu).'], 422);
            }

            $config = config('Documents');
            assert($config instanceof Documents);

            // client_id et uploaded_by = utilisateur connecté, jamais le payload client.
            $clientId = ($actor['role'] ?? null) === 'admin'
                ? (string) ($this->request->getGet('client_id') ?: $userId)
                : (string) $userId;

            $devisId = $this->request->getGet('devis_id');
            $devisId = ($devisId === null || $devisId === '') ? null : (string) $devisId;

            $nomOriginal = rawurldecode($this->request->getHeaderLine('X-File-Name') ?: 'document');

            $storagePath = rtrim($config->storagePath, '/\\') . DIRECTORY_SEPARATOR;
            if (!is_dir($storagePath) && !mkdir($storagePath, 0750, true) && !is_dir($storagePath)) {
                log_message('error', 'DocumentController: impossible de créer ' . $storagePath);
                return $this->failServerError('Stockage indisponible.');
            }

            // Écriture directe dans storagePath (hors public_html), jamais dans /tmp.
            $randomName = bin2hex(random_bytes(16));
            $tempDestination = $storagePath . $randomName . '.tmp';

            $input = @fopen('php://input', 'rb');
            $output = @fopen($tempDestination, 'wb');
            if (!$input || !$output) {
                if (is_resource($input)) {
                    fclose($input);
                }
                if (is_resource($output)) {
                    fclose($output);
                }
                @unlink($tempDestination);
                return $this->fail(['file' => "Impossible d'écrire le fichier."], 500);
            }

            $bytesWritten = stream_copy_to_stream($input, $output);
            fclose($input);
            fclose($output);

            if ($bytesWritten === false || $bytesWritten === 0) {
                @unlink($tempDestination);
                return $this->fail(['file' => 'Fichier vide ou échec de réception.'], 422);
            }

            if ($bytesWritten > $config->maxSizeKB * 1024) {
                @unlink($tempDestination);
                return $this->fail(['file' => 'Fichier trop volumineux (max ' . (int) ($config->maxSizeKB / 1024) . ' Mo).'], 422);
            }

            // Validation MIME réel APRÈS écriture (finfo), jamais sur le Content-Type client.
            $detector = new FinfoMimeTypeDetector();
            $realMime = $detector->detectMimeTypeFromFile($tempDestination);
            if ($realMime === null || !in_array($realMime, $config->allowedMimes, true)) {
                @unlink($tempDestination);
                return $this->fail(['file' => 'Type de fichier non autorisé.'], 422);
            }

            // Renommer en définitif une fois validé (extension déduite du MIME réel).
            $finalName = $randomName . '.' . $this->extensionFromMime($realMime);
            if (!@rename($tempDestination, $storagePath . $finalName)) {
                @unlink($tempDestination);
                return $this->failServerError('Stockage indisponible.');
            }

            // Vérification post-écriture : taille disque vs octets reçus.
            clearstatcache(true, $storagePath . $finalName);
            $storedSize = is_file($storagePath . $finalName) ? (int) @filesize($storagePath . $finalName) : -1;
            if ($storedSize !== (int) $bytesWritten) {
                @unlink($storagePath . $finalName);
                log_message('error', 'DocumentController: fichier tronqué détecté après écriture (reçu {exp} octets, disque {got}).', [
                    'exp' => $bytesWritten,
                    'got' => $storedSize,
                ]);
                return $this->fail(['file' => 'Transfert incomplet détecté (fichier tronqué). Veuillez réessayer.'], 422);
            }

            $model = new DocumentModel();
            if (!$model->insert([
                'client_id' => $clientId,
                'devis_id' => $devisId,
                'type' => $type,
                'nom_original' => $nomOriginal,
                'chemin_stocke' => $finalName,
                'mime_type' => $realMime,
                'taille_bytes' => (int) $bytesWritten,
                'uploaded_by' => (string) $userId,
            ])) {
                @unlink($storagePath . $finalName);
                return $this->fail($model->errors(), 422);
            }

            return $this->respondCreated([
                'message' => 'Document enregistré.',
                'data' => $model->find($model->getInsertID()),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'DocumentController::upload: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur.');
        }
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'bin',
        };
    }

    public function download($documentId = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $userId = $actor['id'] ?? null;
            if (!$userId) {
                return $this->failUnauthorized('Authentification requise.');
            }

            // 1. Charger le document. 404 si absent.
            $model = new DocumentModel();
            $doc = $model->find((string) $documentId);
            if (!$doc) {
                return $this->failNotFound('Document introuvable.');
            }

            // 2. Ownership : admin OK, sinon client propriétaire uniquement.
            $isAdmin = ($actor['role'] ?? null) === 'admin';
            if (!$isAdmin && (string) ($doc['client_id'] ?? '') !== (string) $userId) {
                return $this->failForbidden('Accès refusé.');
            }

            // 3. Chemin réel depuis le stockage privé. 404 si absent du disque.
            $config = config('Documents');
            assert($config instanceof Documents);
            $filePath = rtrim($config->storagePath, '/\\') . DIRECTORY_SEPARATOR . basename((string) $doc['chemin_stocke']);
            if (!is_file($filePath)) {
                return $this->failNotFound('Fichier introuvable.');
            }

            // 4. Servir sous le nom original, jamais sous l'UUID interne.
            return $this->response
                ->download($filePath, null)
                ->setFileName((string) $doc['nom_original']);
        } catch (Throwable $e) {
            log_message('error', 'DocumentController::download: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur.');
        }
    }
}
