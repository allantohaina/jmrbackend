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
