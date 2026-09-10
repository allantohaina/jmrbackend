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

        $contentType = $this->request->getHeaderLine('Content-Type');

        // Transport JSON base64 : le proxy/WAF nginx vide les corps binaires
        // bruts, mais laisse passer l'ASCII. Temporaire, sans validation.
        if (str_starts_with($contentType, 'application/json')) {
            $payload = json_decode((string) file_get_contents('php://input'), true);
            $binary = isset($payload['data']) ? base64_decode((string) $payload['data'], true) : false;

            if ($binary === false || $binary === '') {
                return $this->response->setJSON(['success' => false, 'error' => 'Données base64 invalides ou vides']);
            }

            $filename = time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
            $written = file_put_contents($destinationDir . $filename, $binary);

            if ($written === false) {
                return $this->response->setJSON(['success' => false, 'error' => 'Écriture échouée']);
            }

            return $this->response->setJSON([
                'success' => true,
                'url' => base_url('uploads/site/' . $filename),
                'bytes_received' => strlen($binary),
                'bytes_written' => $written,
            ]);
        }

        // Transport binaire brut historique (ne passe pas le proxy actuel).
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $destination = $destinationDir . $filename;

        $input = fopen('php://input', 'rb');
        $output = fopen($destination, 'wb');
        $bytesWritten = stream_copy_to_stream($input, $output);
        fclose($input);
        fclose($output);

        if (!$bytesWritten) {
            return $this->response->setJSON(['success' => false, 'error' => 'Écriture échouée']);
        }

        return $this->response->setJSON([
            'success' => true,
            'url' => base_url('uploads/site/' . $filename),
        ]);
    }
}
