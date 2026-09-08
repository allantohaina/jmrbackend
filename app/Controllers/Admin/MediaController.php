<?php

namespace App\Controllers\Admin;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Config\Upload;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Throwable;

// Flux SÉPARÉ du contrôleur de documents clients : images publiques du
// contenu du site uniquement (public_html/uploads/site/...), protégé admin.
// Ne pas mutualiser avec Client\DocumentController (stockage privé + ownership).
class MediaController extends ResourceController
{
    protected $format = 'json';

    /** Extension acceptée -> MIME réel attendu (le MIME fait foi, jamais l'extension). */
    private const EXTENSION_MIMES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * Upload en FLUX BRUT (php://input) : contourne le bug CageFS
     * (upload_tmp_dir bloqué sur /tmp, getError()=6 sur tout $_FILES).
     * Le corps de la requête EST le fichier ; le nom d'origine passe en
     * header X-File-Name. Logique métier inchangée (dossier public,
     * images uniquement, getimagesize, contrat de réponse identique).
     */
    public function upload(): ResponseInterface
    {
        try {
            // Protection admin assurée par le filtre de route ['auth', 'admin'].
            $uploadConfig = config('Upload');
            assert($uploadConfig instanceof Upload);

            $originalName = rawurldecode($this->request->getHeaderLine('X-File-Name') ?: 'image');

            $targetDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                log_message('error', 'MediaController: impossible de créer ' . $targetDir);
                return $this->failServerError('Stockage indisponible.');
            }

            $randomName = bin2hex(random_bytes(16));
            $tempDestination = $targetDir . $randomName . '.tmp';

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

            if ($bytesWritten > $uploadConfig->imageMaxSizeMb * 1024 * 1024) {
                @unlink($tempDestination);
                return $this->fail(['file' => 'Image trop volumineuse (max ' . $uploadConfig->imageMaxSizeMb . ' Mo).'], 422);
            }

            // Validation MIME réel APRÈS écriture (finfo), jamais sur le Content-Type client.
            $detector = new FinfoMimeTypeDetector();
            $realMime = $detector->detectMimeTypeFromFile($tempDestination);
            if ($realMime === null || !in_array($realMime, self::EXTENSION_MIMES, true)) {
                @unlink($tempDestination);
                return $this->fail(['file' => 'Image non autorisée (jpg, png, webp uniquement).'], 422);
            }

            $info = @getimagesize($tempDestination);
            if ($info === false) {
                @unlink($tempDestination);
                return $this->fail(['file' => 'Fichier image illisible.'], 422);
            }

            $extension = array_search($realMime, self::EXTENSION_MIMES, true) ?: 'bin';
            $storedName = $randomName . '.' . $extension;
            if (!@rename($tempDestination, $targetDir . $storedName)) {
                @unlink($tempDestination);
                return $this->failServerError('Stockage indisponible.');
            }

            // Vérification post-écriture : taille disque vs octets reçus
            // + re-validation de l'image sur le fichier stocké.
            $storedPath = $targetDir . $storedName;
            clearstatcache(true, $storedPath);
            $storedSize = is_file($storedPath) ? (int) @filesize($storedPath) : -1;
            $storedInfo = is_file($storedPath) ? @getimagesize($storedPath) : false;
            if ($storedSize !== (int) $bytesWritten || $storedInfo === false) {
                @unlink($storedPath);
                log_message('error', 'MediaController: fichier tronqué détecté après écriture (reçu {exp} octets, disque {got}).', [
                    'exp' => $bytesWritten,
                    'got' => $storedSize,
                ]);
                return $this->fail(['file' => 'Transfert incomplet détecté (fichier tronqué). Veuillez réessayer.'], 422);
            }

            return $this->respondCreated([
                'message' => 'Image publiée.',
                'file' => [
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'url' => 'uploads/site/' . $storedName,
                    'mime' => $realMime,
                    'size' => (int) $bytesWritten,
                    'width' => $storedInfo[0] ?? null,
                    'height' => $storedInfo[1] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            log_message('error', 'MediaController::upload: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur.');
        }
    }
}
