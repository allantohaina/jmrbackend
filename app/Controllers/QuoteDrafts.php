<?php

namespace App\Controllers;

use App\Models\QuoteDraftModel;
use App\Application\Quotes\QuoteService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class QuoteDrafts extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) return $this->failUnauthorized('Authentification requise.');
            $drafts = (new QuoteDraftModel())->listForClient((string) $clientId);
            return $this->respond(['data' => $drafts, 'total' => count($drafts)]);
        } catch (Throwable $e) {
            log_message('error', 'QuoteDrafts index error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function save(): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) return $this->failUnauthorized('Authentification requise.');

            $input = $this->getInputData();
            $id = $input['id'] ?? null;
            unset($input['altcha'], $input['id'], $input['technical_files'], $input['saved']);

            $model = new QuoteDraftModel();

            if ($id) {
                $existing = $model->findForClient((string) $id, (string) $clientId);
                if (!$existing) return $this->failNotFound('Brouillon introuvable.');
                $model->update($id, ['payload' => json_encode($input), 'client_id' => $clientId]);
                $saved = $model->findForClient((string) $id, (string) $clientId);
                return $this->respond($saved);
            }

            $model->save([
                'client_id' => $clientId,
                'payload' => json_encode($input),
            ]);
            $draftId = (string) $model->getInsertID();
            $saved = $model->findForClient($draftId, (string) $clientId);
            return $this->respondCreated($saved);
        } catch (Throwable $e) {
            log_message('error', 'QuoteDrafts save error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function submit($id = null): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) return $this->failUnauthorized('Authentification requise.');

            $model = new QuoteDraftModel();
            $draft = $model->findForClient((string) $id, (string) $clientId);
            if (!$draft) return $this->failNotFound('Brouillon introuvable.');

            $payload = is_array($draft['payload']) ? $draft['payload'] : [];
            $payload['name'] = $payload['name'] ?? null;
            $payload['email'] = $payload['email'] ?? null;
            $payload['message'] = $payload['message'] ?? null;
            $payload['client_id'] = $clientId;

            $service = new QuoteService();
            $request = $this->request;
            $result = $service->create($payload, $request);

            if (!$result->isSuccess()) {
                return $this->fail($result->getPayload(), $result->getStatus());
            }

            $model->delete($draft['id']);
            return $this->respondCreated($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'QuoteDrafts submit error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function remove($id = null): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) return $this->failUnauthorized('Authentification requise.');

            $model = new QuoteDraftModel();
            $existing = $model->findForClient((string) $id, (string) $clientId);
            if (!$existing) return $this->failNotFound('Brouillon introuvable.');

            $model->delete($existing['id']);
            return $this->respondDeleted(['message' => 'Brouillon supprimé.']);
        } catch (Throwable $e) {
            log_message('error', 'QuoteDrafts remove error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    private function getInputData(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (Throwable) {}
        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}