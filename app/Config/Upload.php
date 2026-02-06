<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Upload extends BaseConfig
{
    public int $imageMaxSizeMb = 5;
    public int $documentMaxSizeMb = 10;

    public array $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'heic', 'heif'];
    public array $imageMimes = [
        'image/jpg',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
        'image/heic',
        'image/heif',
    ];

    public array $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
    public array $documentMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    ];

    public string $basePath = WRITEPATH . 'uploads';
    public string $imagePath = 'images';
    public string $documentPath = 'documents';
}
