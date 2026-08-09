<?php

namespace App\Controllers;

use App\Application\BonLivraison\BonLivraisonService;
use App\Application\Shared\Result;
use CodeIgniter\RESTful\ResourceController;

class BonLivraison extends ResourceController
{
    protected $format = 'json';
    private ?BonLivraisonService $service = null;

    private function service(): BonLivraisonService
    {
        if ($this->service === null) {
            $this->service = service('bonLivraisonService');
        }
        return $this->service;
    }

    public function index()
    {
        $result = $this->service()->list();
        return $this->respond($result->getPayload());
    }

    public function show($id = null)
    {
        $result = $this->service()->getById($id);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respond($result->getPayload());
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $result = $this->service()->create($data);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respondCreated($result->getPayload());
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $result = $this->service()->update($id, $data);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respond($result->getPayload());
    }

    public function delete($id = null)
    {
        $result = $this->service()->delete($id);
        if (!$result->isSuccess()) {
            return $this->respond($result->getPayload(), $result->getStatus());
        }
        return $this->respondDeleted($result->getPayload());
    }

    public function sign($id = null)
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut signer un document.');
            }

            $input = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];
            if (!is_array($input)) {
                $input = [];
            }
            $signatureName = trim((string)($input['admin_signature_name'] ?? ''));
            $signatureAt = $input['admin_signature_at'] ?? date('Y-m-d H:i:s');

            if ($signatureName === '') {
                return $this->failValidationErrors(['admin_signature_name' => 'Le nom du signataire est requis.']);
            }

            $model = new \App\Models\BonLivraisonModel();
            $record = $model->find($id);
            if (!$record) {
                return $this->failNotFound('Bon de livraison introuvable.');
            }

            $updated = $model->update($id, [
                'admin_signature_name' => $signatureName,
                'admin_signature_at'   => is_string($signatureAt) ? $signatureAt : date('Y-m-d H:i:s'),
            ]);

            if (!$updated) {
                return $this->failServerError('Impossible d\'enregistrer la signature.');
            }

            $all = $model->getWithCommande();
            $fresh = null;
            foreach ($all as $item) {
                if (($item['id'] ?? null) === $id) {
                    $fresh = $item;
                    break;
                }
            }
            return $this->respond($fresh ?? $model->find($id));
        } catch (\Throwable $e) {
            log_message('error', 'BonLivraison sign error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }
}
