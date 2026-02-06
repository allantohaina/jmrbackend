<?php

namespace App\Libraries;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Upload;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

class UploadGuard
{
    private Upload $config;
    private FinfoMimeTypeDetector $detector;

    public function __construct(?Upload $config = null)
    {
        $this->config = $config ?? config('Upload');
        $this->detector = new FinfoMimeTypeDetector();
    }

    /**
     * @return array{0: bool, 1: string|null, 2: array|null}
     */
    public function validate(UploadedFile $file, string $type): array
    {
        if (!$file->isValid()) {
            return [false, lang('Upload.errors.invalid_upload'), null];
        }

        $type = strtolower($type);
        if (!in_array($type, ['image', 'document'], true)) {
            return [false, lang('Upload.errors.invalid_type'), null];
        }

        $maxSizeMb = $type === 'image' ? $this->config->imageMaxSizeMb : $this->config->documentMaxSizeMb;
        $allowedExtensions = $type === 'image' ? $this->config->imageExtensions : $this->config->documentExtensions;
        $allowedMimes = $type === 'image' ? $this->config->imageMimes : $this->config->documentMimes;

        $extension = strtolower((string) $file->getClientExtension());
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            return [false, lang('Upload.errors.extension'), null];
        }

        $sizeBytes = (int) $file->getSize();
        $maxBytes = $maxSizeMb * 1024 * 1024;
        if ($sizeBytes > $maxBytes) {
            return [false, lang('Upload.errors.max_size', [$maxSizeMb]), null];
        }

        $tempPath = $file->getTempName();
        $mime = $tempPath ? $this->detector->detectMimeTypeFromFile($tempPath) : null;
        if ($mime === null || !in_array($mime, $allowedMimes, true)) {
            return [false, lang('Upload.errors.mime'), null];
        }

        return [true, null, [
            'extension' => $extension,
            'mime' => $mime,
            'size' => $sizeBytes,
        ]];
    }

    /**
     * @return array{path: string, name: string}
     */
    public function store(UploadedFile $file, string $type): array
    {
        $targetDir = $this->resolveTargetDir($type);
        $this->ensureDirectory($targetDir);

        $name = $file->getRandomName();
        $file->move($targetDir, $name);

        return [
            'path' => $targetDir,
            'name' => $name,
        ];
    }

    private function resolveTargetDir(string $type): string
    {
        $sub = $type === 'image' ? $this->config->imagePath : $this->config->documentPath;
        return rtrim($this->config->basePath, '/\\') . DIRECTORY_SEPARATOR . $sub;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
