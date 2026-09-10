<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;

class MediaController extends BaseController
{
    private function chunksDir(string $uploadId): string
    {
        $dir = WRITEPATH . 'chunks/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId) . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function uploadChunk()
    {
        $uploadId = $this->request->getPost('upload_id');
        $chunkIndex = $this->request->getPost('chunk_index');
        $chunkData = $this->request->getPost('data');

        if (empty($uploadId) || $chunkIndex === null || empty($chunkData)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Paramètres manquants',
                'debug' => ['post_keys' => array_keys($this->request->getPost() ?? [])],
            ]);
        }

        $binary = base64_decode($chunkData, true);
        if ($binary === false) {
            return $this->response->setJSON(['success' => false, 'error' => 'Base64 invalide']);
        }

        $dir = $this->chunksDir($uploadId);
        file_put_contents($dir . 'chunk_' . (int) $chunkIndex, $binary);

        return $this->response->setJSON(['success' => true]);
    }

    public function finalizeUpload()
    {
        $uploadId = $this->request->getPost('upload_id');
        $totalChunks = (int) $this->request->getPost('total_chunks');

        if (empty($uploadId) || $totalChunks < 1) {
            return $this->response->setJSON(['success' => false, 'error' => 'Paramètres manquants']);
        }

        $dir = $this->chunksDir($uploadId);
        $destinationDir = FCPATH . 'uploads/site/';
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $output = fopen($destinationDir . $filename, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $dir . 'chunk_' . $i;
            if (!file_exists($chunkPath)) {
                fclose($output);
                return $this->response->setJSON(['success' => false, 'error' => "Morceau $i manquant"]);
            }
            fwrite($output, file_get_contents($chunkPath));
        }
        fclose($output);

        // Nettoyage des morceaux temporaires
        array_map('unlink', glob($dir . 'chunk_*'));
        rmdir($dir);

        return $this->response->setJSON([
            'success' => true,
            'url' => base_url('uploads/site/' . $filename),
        ]);
    }

    // Supprime une ancienne image remplacée (nom seul, restreint au dossier site).
    public function delete()
    {
        $filename = basename((string) $this->request->getPost('filename'));

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return $this->response->setJSON(['success' => false, 'error' => 'Nom de fichier manquant']);
        }

        $path = FCPATH . 'uploads/site/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }

        return $this->response->setJSON(['success' => true]);
    }
}
