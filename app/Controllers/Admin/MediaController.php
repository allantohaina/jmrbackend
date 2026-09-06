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

    public function upload(): ResponseInterface
    {
        try {
            // Protection admin assurée par le filtre de route ['auth', 'admin'].
            $file = $this->request->getFile('file');
            if ($file === null || !$file->isValid() || $file->hasMoved()) {
                return $this->fail(['file' => 'Fichier requis ou invalide.'], 422);
            }

            $uploadConfig = config('Upload');
            assert($uploadConfig instanceof Upload);

            $extension = strtolower((string) $file->getClientExtension());
            if (!isset(self::EXTENSION_MIMES[$extension])) {
                return $this->fail(['file' => 'Image non autorisée (jpg, jpeg, png, webp uniquement).'], 422);
            }

            $sizeBytes = (int) $file->getSize();
            if ($sizeBytes > $uploadConfig->imageMaxSizeMb * 1024 * 1024) {
                return $this->fail(['file' => 'Image trop volumineuse (max ' . $uploadConfig->imageMaxSizeMb . ' Mo).'], 422);
            }

            $detector = new FinfoMimeTypeDetector();
            $tempPath = $file->getTempName();
            $realMime = $tempPath ? $detector->detectMimeTypeFromFile($tempPath) : null;
            if ($realMime === null || $realMime !== self::EXTENSION_MIMES[$extension]) {
                return $this->fail(['file' => 'Type MIME réel non autorisé.'], 422);
            }

            $info = @getimagesize($tempPath);
            if ($info === false) {
                return $this->fail(['file' => 'Fichier image illisible.'], 422);
            }

            $targetDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                log_message('error', 'MediaController: impossible de créer ' . $targetDir);
                return $this->failServerError('Stockage indisponible.');
            }

            $storedName = $file->getRandomName();
            $file->move($targetDir, $storedName);

            // Vérification post-écriture : un fichier partiellement uploadé peut
            // passer la validation initiale puis être tronqué sur disque
            // (timeout / coupure en plein transfert). On compare la taille
            // réelle sur disque à la taille déclarée, et on re-valide l'image
            // sur le fichier stocké (pas seulement sur le tmp).
            $storedPath = $targetDir . $storedName;
            clearstatcache(true, $storedPath);
            $storedSize = is_file($storedPath) ? (int) @filesize($storedPath) : -1;
            $storedInfo = is_file($storedPath) ? @getimagesize($storedPath) : false;
            if ($storedSize !== $sizeBytes || $storedInfo === false) {
                @unlink($storedPath);
                log_message('error', 'MediaController: fichier tronqué détecté après écriture (attendu {exp} octets, disque {got}).', [
                    'exp' => $sizeBytes,
                    'got' => $storedSize,
                ]);
                return $this->fail(['file' => 'Transfert incomplet détecté (fichier tronqué). Veuillez réessayer.'], 422);
            }

            return $this->respondCreated([
                'message' => 'Image publiée.',
                'file' => [
                    'original_name' => $file->getClientName(),
                    'stored_name' => $storedName,
                    'url' => 'uploads/site/' . $storedName,
                    'mime' => $realMime,
                    'size' => $sizeBytes,
                    'width' => $info[0] ?? null,
                    'height' => $info[1] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            log_message('error', 'MediaController::upload: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur.');
        }
    }
}
