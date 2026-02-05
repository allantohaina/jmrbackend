<?php

namespace App\Controllers;

use App\Models\ConsentModel;
use App\Models\DataRequestModel;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Legal extends ResourceController
{
    protected $format = 'json';

    public function privacy()
    {
        return $this->respond($this->loadLegalDoc('privacy'));
    }

    public function terms()
    {
        return $this->respond($this->loadLegalDoc('terms'));
    }

    public function cookies()
    {
        return $this->respond($this->loadLegalDoc('cookies'));
    }

    public function disclaimer()
    {
        return $this->respond($this->loadLegalDoc('disclaimer'));
    }

    public function accessibility()
    {
        return $this->respond($this->loadLegalDoc('accessibility'));
    }

    public function consent()
    {
        try {
            $input = $this->getInputData();

            $subject = $input['subject'] ?? null;
            $version = $input['version'] ?? null;
            $granted = $input['granted'] ?? null;
            $userId = $input['user_id'] ?? null;

            if ($subject === null || $version === null || $granted === null) {
                return $this->fail('subject, version et granted requis', 400);
            }

            $model = new ConsentModel();
            $id = $this->uuidV4();

            $model->insert([
                'id' => $id,
                'user_id' => $userId,
                'subject' => $subject,
                'version' => $version,
                'granted' => (bool) $granted,
                'granted_at' => (bool) $granted ? date('Y-m-d H:i:s') : null,
                'revoked_at' => (bool) $granted ? null : date('Y-m-d H:i:s'),
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
            ]);

            return $this->respondCreated([
                'message' => 'Consentement enregistré',
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            return $this->fail('Erreur lors de l\'enregistrement du consentement', 500);
        }
    }

    public function dataRequest()
    {
        try {
            $input = $this->getInputData();

            $requestType = $input['request_type'] ?? null;
            $email = $input['email'] ?? null;
            $userId = $input['user_id'] ?? null;
            $details = $input['details'] ?? null;

            if ($requestType === null) {
                return $this->fail('request_type requis', 400);
            }

            $model = new DataRequestModel();
            $id = $this->uuidV4();

            $model->insert([
                'id' => $id,
                'user_id' => $userId,
                'email' => $email,
                'request_type' => $requestType,
                'status' => 'received',
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => null,
                'completed_at' => null,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
            ]);

            return $this->respondCreated([
                'message' => 'Demande enregistrée',
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            return $this->fail('Erreur lors de la création de la demande', 500);
        }
    }

    private function loadLegalDoc(string $name): array
    {
        $path = APPPATH . 'Legal' . DIRECTORY_SEPARATOR . $name . '.md';
        $content = is_file($path) ? file_get_contents($path) : '';

        return [
            'name' => $name,
            'version' => getenv('LEGAL_DOC_VERSION') ?: '1.0.0',
            'content' => $content,
        ];
    }

    private function getInputData(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
