<?php
namespace App\Controllers\Client;
use App\Controllers\BaseController;

class DocumentController extends BaseController
{
    private string $storagePath = '/home/jmrtexti/private_uploads/documents/';

    private function chunksDir(string $uploadId): string
    {
        $dir = WRITEPATH . 'doc-chunks/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId) . '/';
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
        $type = $this->request->getPost('type'); // preuve_paiement, devis, recu
        $devisId = $this->request->getPost('devis_id');
        $nomOriginal = $this->request->getPost('nom_original') ?? 'document.pdf';

        if (empty($uploadId) || $totalChunks < 1 || empty($type)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Paramètres manquants']);
        }

        if (!in_array($type, ['preuve_paiement', 'devis', 'recu'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Type invalide']);
        }

        $dir = $this->chunksDir($uploadId);
        $tempPath = $this->storagePath . 'tmp_' . bin2hex(random_bytes(8));
        $output = fopen($tempPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $dir . 'chunk_' . $i;
            if (!file_exists($chunkPath)) {
                fclose($output);
                @unlink($tempPath);
                return $this->response->setJSON(['success' => false, 'error' => "Morceau $i manquant"]);
            }
            fwrite($output, file_get_contents($chunkPath));
        }
        fclose($output);
        array_map('unlink', glob($dir . 'chunk_*'));
        rmdir($dir);

        // Validation MIME réelle après écriture complète
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tempPath);
        finfo_close($finfo);

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mime, $allowedMimes)) {
            @unlink($tempPath);
            return $this->response->setJSON(['success' => false, 'error' => 'Type de fichier non autorisé']);
        }

        $extension = match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        };
        $finalName = bin2hex(random_bytes(16)) . '.' . $extension;
        rename($tempPath, $this->storagePath . $finalName);

        // Auth projet : utilisateur JWT via le filtre auth ($this->request->user),
        // pas d'helper auth() ni de isAdmin() dans ce codebase.
        $user = $this->request->user ?? [];
        $userId = $user['id'] ?? null;

        $documentModel = new \App\Models\DocumentModel();
        $documentModel->insert([
            'client_id'     => $userId,
            'devis_id'      => $devisId ?: null,
            'type'          => $type,
            'nom_original'  => $nomOriginal,
            'chemin_stocke' => $finalName,
            'mime_type'     => $mime,
            'taille_bytes'  => filesize($this->storagePath . $finalName),
            'uploaded_by'   => $userId,
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    public function download($documentId)
    {
        $documentModel = new \App\Models\DocumentModel();
        $doc = $documentModel->find($documentId);

        if (!$doc) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $user = $this->request->user ?? [];
        $isAdmin = ($user['role'] ?? null) === 'admin';
        if (!$isAdmin && ($doc['client_id'] ?? null) != ($user['id'] ?? null)) {
            return $this->response->setStatusCode(403)->setBody('Accès refusé.');
        }

        $filePath = $this->storagePath . $doc['chemin_stocke'];
        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($filePath, null)->setFileName($doc['nom_original']);
    }
}
