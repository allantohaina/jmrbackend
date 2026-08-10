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
            if (!$clientId) {
                log_message('warning', 'QuoteDrafts index: Auth failed - no user ID in request');
                return $this->failUnauthorized('Authentification requise.');
            }
            log_message('info', "QuoteDrafts index: Listing drafts for client_id={$clientId}");
            $drafts = (new QuoteDraftModel())->listForClient((string) $clientId);
            log_message('info', "QuoteDrafts index: Found " . count($drafts) . " drafts for client_id={$clientId}");
            return $this->respond(['data' => $drafts, 'total' => count($drafts)]);
        } catch (Throwable $e) {
            log_message('error', "QuoteDrafts index error: {$e->getMessage()}\nClient ID: " . ($this->request->user['id'] ?? 'null') . "\nFile: {$e->getFile()}:{$e->getLine()}\nTrace: {$e->getTraceAsString()}");
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function save(): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) {
                log_message('warning', 'QuoteDrafts save: Auth failed - no user ID in request');
                return $this->failUnauthorized('Authentification requise.');
            }

            $input = $this->getInputData();
            $id = $input['id'] ?? null;
            unset($input['altcha'], $input['id'], $input['technical_files'], $input['saved']);

            log_message('info', "QuoteDrafts save: client_id={$clientId}, draft_id=" . ($id ?? 'new') . ", payload_keys=" . implode(',', array_keys($input)));

            $model = new QuoteDraftModel();

            if ($id) {
                $existing = $model->findForClient((string) $id, (string) $clientId);
                if (!$existing) {
                    log_message('warning', "QuoteDrafts save: Draft not found id={$id} client_id={$clientId}");
                    return $this->failNotFound('Brouillon introuvable.');
                }
                $model->update($id, ['payload' => json_encode($input), 'client_id' => $clientId]);
                $saved = $model->findForClient((string) $id, (string) $clientId);
                log_message('info', "QuoteDrafts save: Updated draft id={$id} for client_id={$clientId}");
                return $this->respond($saved);
            }

            $draftId = $model->generateId();
            $model->save([
                'id' => $draftId,
                'client_id' => $clientId,
                'payload' => json_encode($input),
            ]);
            $saved = $model->findForClient($draftId, (string) $clientId);
            log_message('info', "QuoteDrafts save: Created draft id={$draftId} for client_id={$clientId}");
            return $this->respondCreated($saved);
        } catch (Throwable $e) {
            log_message('error', "QuoteDrafts save error: {$e->getMessage()}\nClient ID: " . ($this->request->user['id'] ?? 'null') . "\nInput: " . json_encode($input ?? []) . "\nFile: {$e->getFile()}:{$e->getLine()}\nTrace: {$e->getTraceAsString()}");
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function submit($id = null): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) {
                log_message('warning', 'QuoteDrafts submit: Auth failed - no user ID in request');
                return $this->failUnauthorized('Authentification requise.');
            }

            log_message('info', "QuoteDrafts submit: draft_id={$id}, client_id={$clientId}");

            $model = new QuoteDraftModel();
            $draft = $model->findForClient((string) $id, (string) $clientId);
            if (!$draft) {
                log_message('warning', "QuoteDrafts submit: Draft not found id={$id} client_id={$clientId}");
                return $this->failNotFound('Brouillon introuvable.');
            }

            $payload = is_array($draft['payload']) ? $draft['payload'] : [];
            $payload['name'] = $payload['name'] ?? null;
            $payload['email'] = $payload['email'] ?? null;
            $payload['message'] = $payload['message'] ?? null;
            $payload['client_id'] = $clientId;

            log_message('info', "QuoteDrafts submit: Converting draft id={$id} to quote, payload_keys=" . implode(',', array_keys($payload)));

            $service = new QuoteService();
            $request = $this->request;
            $result = $service->create($payload, $request);

            if (!$result->isSuccess()) {
                log_message('warning', "QuoteDrafts submit: QuoteService failed for draft id={$id}, status={$result->getStatus()}");
                return $this->fail($result->getPayload(), $result->getStatus());
            }

            $model->delete($draft['id']);
            log_message('info', "QuoteDrafts submit: Draft id={$id} submitted and deleted successfully");
            return $this->respondCreated($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', "QuoteDrafts submit error: {$e->getMessage()}\nDraft ID: {$id}\nClient ID: " . ($this->request->user['id'] ?? 'null') . "\nFile: {$e->getFile()}:{$e->getLine()}\nTrace: {$e->getTraceAsString()}");
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function remove($id = null): ResponseInterface
    {
        try {
            $clientId = $this->request->user['id'] ?? null;
            if (!$clientId) {
                log_message('warning', 'QuoteDrafts remove: Auth failed - no user ID in request');
                return $this->failUnauthorized('Authentification requise.');
            }

            log_message('info', "QuoteDrafts remove: draft_id={$id}, client_id={$clientId}");

            $model = new QuoteDraftModel();
            $existing = $model->findForClient((string) $id, (string) $clientId);
            if (!$existing) {
                log_message('warning', "QuoteDrafts remove: Draft not found id={$id} client_id={$clientId}");
                return $this->failNotFound('Brouillon introuvable.');
            }

            $model->delete($existing['id']);
            log_message('info', "QuoteDrafts remove: Draft id={$id} deleted successfully");
            return $this->respondDeleted(['message' => 'Brouillon supprimé.']);
        } catch (Throwable $e) {
            log_message('error', "QuoteDrafts remove error: {$e->getMessage()}\nDraft ID: {$id}\nClient ID: " . ($this->request->user['id'] ?? 'null') . "\nFile: {$e->getFile()}:{$e->getLine()}\nTrace: {$e->getTraceAsString()}");
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