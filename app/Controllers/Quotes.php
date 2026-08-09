<?php

namespace App\Controllers;

use App\Application\Quotes\QuoteService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Quotes extends ResourceController
{
    protected $format = 'json';

    private ?QuoteService $quoteService = null;

    private function quoteService(): QuoteService
    {
        if ($this->quoteService === null) {
            $this->quoteService = new QuoteService();
        }

        return $this->quoteService;
    }

    public function index(): ResponseInterface
    {
        try {
            $userId = $this->request->user['id'] ?? null;
            $result = $this->quoteService()->list($this->request, $userId);
            return $this->respond($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Quotes index error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $result = $this->quoteService()->create($input, $this->request);

            if ($result->isSuccess()) {
                return $this->respondCreated($result->getPayload());
            }

            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Quotes create error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $result = $this->quoteService()->getQuoteById($id);
            if (!$result) {
                return $this->failNotFound('Devis introuvable');
            }
            return $this->respond($result);
        } catch (Throwable $e) {
            log_message('error', 'Quotes show error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function share($hash = null): ResponseInterface
    {
        try {
            $result = $this->quoteService()->getQuoteById($hash);
            if (!$result) {
                return $this->failNotFound('Devis introuvable');
            }
            // Only expose safe fields for public view
            return $this->respond([
                'id' => $result['id'],
                'name' => $result['name'],
                'category' => $result['category'],
                'status' => $result['status'],
                'amount' => $result['amount'],
                'deposit_paid' => $result['deposit_paid'],
                'balance_paid' => $result['balance_paid'],
                'created_at' => $result['created_at'],
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Quotes share error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function updateStatus($id = null): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $status = $input['status'] ?? null;
            $additionalData = array_filter($input, fn($key) => !in_array($key, ['status']), ARRAY_FILTER_USE_KEY);

            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut modifier un devis.');
            }
            $result = $this->quoteService()->updateStatus($id, $status, $additionalData, $actor);

            if ($result->isSuccess()) {
                return $this->respond($result->getPayload());
            }

            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Quotes updateStatus error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function confirm($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $result = $this->quoteService()->confirmByClient($id, $actor);
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'Quotes confirm error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function notifications(): ResponseInterface
    {
        try {
            // Get user from auth context if available
            $userId = $this->request->user['id'] ?? null;
            $notifications = $this->quoteService()->getNotifications($userId);

            return $this->respond(['data' => $notifications]);
        } catch (Throwable $e) {
            log_message('error', 'Quotes notifications error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function markNotificationRead($id = null): ResponseInterface
    {
        try {
            $result = $this->quoteService()->markAsRead($id);

            if ($result->isSuccess()) {
                return $this->respond($result->getPayload());
            }

            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Quotes markNotificationRead error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    private function getInputData(): array
    {
        // If multipart/form-data, read from getPost() directly
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'multipart/form-data')) {
            $post = $this->request->getPost();
            return is_array($post) ? $post : [];
        }

        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (Throwable) {
            // Not JSON, fall through
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}
