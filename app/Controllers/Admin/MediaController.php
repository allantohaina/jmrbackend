<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;

class MediaController extends BaseController
{
    public function upload()
    {
        $destinationDir = FCPATH . 'uploads/site/';
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $dataField = $this->request->getPost('data');

        if (empty($dataField)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Champ data manquant ou vide',
                'debug' => [
                    'post_keys' => array_keys($this->request->getPost() ?? []),
                ],
            ]);
        }

        $binary = base64_decode($dataField, true);

        if ($binary === false || $binary === '') {
            return $this->response->setJSON(['success' => false, 'error' => 'Base64 invalide']);
        }

        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $written = file_put_contents($destinationDir . $filename, $binary);

        if ($written === false) {
            return $this->response->setJSON(['success' => false, 'error' => 'Écriture disque échouée']);
        }

        return $this->response->setJSON([
            'success' => true,
            'url' => base_url('uploads/site/' . $filename),
            'bytes_written' => $written,
        ]);
    }
}
