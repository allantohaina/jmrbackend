<?php

namespace App\Application\Legal;

use App\Application\Shared\Result;
use App\Models\ConsentModel;
use App\Models\DataRequestModel;
use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

class LegalService
{
    public function getDoc(string $name): Result
    {
        $path = APPPATH . 'Legal' . DIRECTORY_SEPARATOR . $name . '.md';
        $content = is_file($path) ? file_get_contents($path) : '';

        return Result::ok([
            'name' => $name,
            'version' => getenv('LEGAL_DOC_VERSION') ?: '1.0.0',
            'content' => $content,
        ]);
    }

    public function recordConsent(array $input, IncomingRequest $request): Result
    {
        try {
            $subject = $input['subject'] ?? null;
            $version = $input['version'] ?? null;
            $granted = $input['granted'] ?? null;
            $userId = $input['user_id'] ?? null;

            if ($subject === null || $version === null || $granted === null) {
                return Result::fail(lang('Legal.consent.required'), 400);
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
                'ip_address' => $request->getIPAddress(),
                'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
            ]);

            return Result::created([
                'message' => lang('Legal.consent.saved'),
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            return Result::fail(lang('Legal.consent.error'), 500);
        }
    }

    public function recordDataRequest(array $input, IncomingRequest $request): Result
    {
        try {
            $requestType = $input['request_type'] ?? null;
            $email = $input['email'] ?? null;
            $userId = $input['user_id'] ?? null;
            $details = $input['details'] ?? null;

            if ($requestType === null) {
                return Result::fail(lang('Legal.data_request.required'), 400);
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
                'ip_address' => $request->getIPAddress(),
                'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
            ]);

            return Result::created([
                'message' => lang('Legal.data_request.saved'),
                'id' => $id,
            ]);
        } catch (Throwable $e) {
            return Result::fail(lang('Legal.data_request.error'), 500);
        }
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

