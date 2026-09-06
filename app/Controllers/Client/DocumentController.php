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

    /** Extension acceptée -> MIME réel attendu (le MIME fait foi, jamais l'extension). */
    private const EXTENSION_MIMES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    private const ALLOWED_TYPES = ['preuve_paiement', 'devis', 'recu'];

    public function upload(): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $userId = $actor['id'] ?? null;
            if (!$userId) {
                return $this->failUnauthorized('Authentification requise.');
            }

            $file = $this->request->getFile('file');
            if ($file === null || !$file->isValid() || $file->hasMoved()) {
                return $this->fail(['file' => 'Fichier requis ou invalide.'], 422);
            }

            $type = strtolower((string) ($this->request->getPost('type') ?? ''));
            if (!in_array($type, self::ALLOWED_TYPES, true)) {
                return $this->fail(['type' => 'Type de document invalide (preuve_paiement, devis, recu).'], 422);
            }

            $config = config('Documents');
            assert($config instanceof Documents);

            // client_id et uploaded_by = utilisateur connecté, jamais le payload client.
            $clientId = ($actor['role'] ?? null) === 'admin'
                ? (string) ($this->request->getPost('client_id') ?: $userId)
                : (string) $userId;

            $devisId = $this->request->getPost('devis_id');
            $devisId = ($devisId === null || $devisId === '') ? null : (string) $devisId;

            $extension = strtolower((string) $file->getClientExtension());
            if (!isset(self::EXTENSION_MIMES[$extension])) {
                return $this->fail(['file' => 'Extension non autorisée (pdf, jpg, jpeg, png uniquement).'], 422);
            }

            $sizeBytes = (int) $file->getSize();
            if ($sizeBytes > $config->maxSizeKB * 1024) {
                return $this->fail(['file' => 'Fichier trop volumineux (max ' . (int) ($config->maxSizeKB / 1024) . ' Mo).'], 422);
            }

            $detector = new FinfoMimeTypeDetector();
            $tempPath = $file->getTempName();
            $realMime = $tempPath ? $detector->detectMimeTypeFromFile($tempPath) : null;
            if ($realMime === null || !in_array($realMime, $config->allowedMimes, true) || $realMime !== self::EXTENSION_MIMES[$extension]) {
                return $this->fail(['file' => 'Type MIME réel non autorisé.'], 422);
            }

            $storagePath = rtrim($config->storagePath, '/\\') . DIRECTORY_SEPARATOR;
            if (!is_dir($storagePath) && !mkdir($storagePath, 0750, true) && !is_dir($storagePath)) {
                log_message('error', 'DocumentController: impossible de créer ' . $storagePath);
                return $this->failServerError('Stockage indisponible.');
            }

            $storedName = $file->getRandomName();
            $file->move($storagePath, $storedName);

            $model = new DocumentModel();
            if (!$model->insert([
                'client_id' => $clientId,
                'devis_id' => $devisId,
                'type' => $type,
                'nom_original' => $file->getClientName(),
                'chemin_stocke' => $storedName,
                'mime_type' => $realMime,
                'taille_bytes' => $sizeBytes,
                'uploaded_by' => (string) $userId,
            ])) {
                @unlink($storagePath . $storedName);
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
