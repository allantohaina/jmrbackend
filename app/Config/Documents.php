<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Documents extends BaseConfig
{
    /**
     * Stockage des documents clients confidentiels.
     * DOIT rester hors de public_html : aucun accès HTTP direct possible,
     * téléchargement uniquement via Client\DocumentController::download()
     * qui vérifie l'ownership (client propriétaire ou admin).
     */
    public string $storagePath = '/home/jmrtexti/private_uploads/documents/';

    public array $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    /** Taille max en Ko (10240 = 10 Mo). */
    public int $maxSizeKB = 10240;
}
