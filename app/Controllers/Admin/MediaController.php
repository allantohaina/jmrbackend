<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;

class MediaController extends BaseController
{
    public function upload()
    {
        $destinationDir = FCPATH . 'uploads/site/';

        if (!is_dir($destinationDir)) {
            $mkdirResult = mkdir($destinationDir, 0755, true);
            if (!$mkdirResult) {
                return $this->response->setJSON(['success' => false, 'error' => 'mkdir a échoué', 'dir' => $destinationDir]);
            }
        }

        if (!is_writable($destinationDir)) {
            return $this->response->setJSON(['success' => false, 'error' => 'dossier non inscriptible', 'dir' => $destinationDir]);
        }

        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $destination = $destinationDir . $filename;

        $rawInput = file_get_contents('php://input');
        $inputLength = strlen($rawInput);

        if ($inputLength === 0) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'php://input est vide — probablement déjà consommé par un filtre CI4 avant le contrôleur',
                'content_length_header' => $this->request->getHeaderLine('Content-Length'),
            ]);
        }

        $written = file_put_contents($destination, $rawInput);

        if ($written === false) {
            return $this->response->setJSON(['success' => false, 'error' => 'file_put_contents a échoué', 'destination' => $destination]);
        }

        return $this->response->setJSON([
            'success' => true,
            'url' => base_url('uploads/site/' . $filename),
            'bytes_received' => $inputLength,
            'bytes_written' => $written,
        ]);
    }
}
