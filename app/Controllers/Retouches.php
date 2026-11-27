<?php

namespace App\Controllers;

use App\Application\Retouches\RetoucheService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Retouches extends ResourceController
{
    protected $format = 'json';

    private function input(): array
    {
        $data = $this->request->getJSON(true);
        return is_array($data) ? $data : (array)$this->request->getPost();
    }

    public function list(): ResponseInterface
    {
        try {
            $quoteId = (string)($this->request->getGet('quote_id') ?? '');
            $result = (new RetoucheService())->listByQuote($quoteId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $input = $this->input();
            $input['created_by'] = $this->request->user['id'] ?? null;
            $result = (new RetoucheService())->create($input);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function include($id = null): ResponseInterface
    {
        try {
            $input = $this->input();
            $result = (new RetoucheService())->includeRetouche((string)$id, $input['price'] ?? null);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function reject($id = null): ResponseInterface
    {
        try {
            $result = (new RetoucheService())->reject((string)$id);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
