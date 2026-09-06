<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

// TODO-DIAG: contrôleur temporaire de diagnostic upload.
// À SUPPRIMER dès que la cause du blocage est identifiée et corrigée,
// ainsi que ses routes (debug-upload/*) dans app/Config/Routes.php.
class DebugUpload extends ResourceController
{
    protected $format = 'json';

    public function info()
    {
        $tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();

        return $this->respond([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_tmp_dir' => $tmpDir,
            'upload_tmp_dir_writable' => is_writable($tmpDir),
            'php_version' => phpversion(),
            'sapi' => php_sapi_name(),
        ]);
    }

    public function test()
    {
        $file = $this->request->getFile('test_file');

        if ($file === null) {
            return $this->respond([
                'received' => false,
                'error' => 'Aucun champ test_file reçu (vérifier enctype multipart + post_max_size).',
            ], 422);
        }

        return $this->respond([
            'received' => true,
            'isValid' => $file->isValid(),
            'getError' => $file->getError(),
            'getErrorString' => method_exists($file, 'getErrorString') ? $file->getErrorString() : null,
            'getSize' => $file->getSize(),
            'getMimeType' => $file->getMimeType(),
            // Rappel: 1=UPLOAD_ERR_INI_SIZE (upload_max_filesize), 2=UPLOAD_ERR_FORM_SIZE (MAX_FILE_SIZE HTML),
            // 6=UPLOAD_ERR_NO_TMP_DIR, 7=UPLOAD_ERR_CANT_WRITE (permissions).
        ]);
    }
}
