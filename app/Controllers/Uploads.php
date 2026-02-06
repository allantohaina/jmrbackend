<?php

namespace App\Controllers;

use App\Libraries\UploadGuard;
use CodeIgniter\RESTful\ResourceController;
use Config\Upload;
use Throwable;

class Uploads extends ResourceController
{
    protected $format = 'json';

    public function image()
    {
        return $this->handleUpload('image', 'uploadImage');
    }

    public function document()
    {
        return $this->handleUpload('document', 'uploadDocument');
    }

    private function handleUpload(string $type, string $validationGroup)
    {
        try {
            if (! $this->validate($validationGroup)) {
                return $this->fail($this->validator->getErrors(), 422);
            }

            $file = $this->request->getFile('file');
            if ($file === null) {
                return $this->fail(['file' => lang('Upload.errors.invalid_upload')], 400);
            }

            $guard = new UploadGuard();
            [$ok, $error, $meta] = $guard->validate($file, $type);
            if (! $ok) {
                return $this->fail(['file' => $error], 422);
            }

            $stored = $guard->store($file, $type);

            return $this->respondCreated([
                'message' => lang('Upload.success'),
                'type' => $type,
                'file' => [
                    'original_name' => $file->getClientName(),
                    'stored_name' => $stored['name'],
                    'mime' => $meta['mime'] ?? null,
                    'size' => $meta['size'] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->fail(lang('Common.errors.unexpected'), 500);
        }
    }
}
