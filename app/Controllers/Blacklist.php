<?php

namespace App\Controllers;

use App\History\AdminHistory;
use App\Models\BlacklistModel;
use CodeIgniter\RESTful\ResourceController;

class Blacklist extends ResourceController
{
    public function index()
    {
        $model = new BlacklistModel();
        $perPage = $this->request->getGet('per_page') ?? 20;
        $page = $this->request->getGet('page') ?? 1;

        $entries = $model->orderBy('created_at', 'DESC')
            ->paginate($perPage, 'default', $page);

        return $this->respond([
            'data' => $entries ?: [],
            'total' => $model->pager->getTotal(),
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function create()
    {
        $json = $this->request->getJSON(true);
        $email = $json['email'] ?? null;
        $ipAddress = $json['ip_address'] ?? null;
        $reason = $json['reason'] ?? null;

        if (!$email && !$ipAddress) {
            return $this->failValidationErrors('email or ip_address is required.');
        }

        $data = [
            'email' => $email,
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $model = new BlacklistModel();
        if (!$model->insert($data)) {
            return $this->failServerError('Erreur lors de l\'ajout à la blacklist.');
        }

        $id = $model->getInsertID();
        $entry = $model->find($id);

        (new AdminHistory())->logUserUpdate(
            $this->request,
            $this->request->user['id'] ?? null,
            'system',
            null,
            ['blacklisted' => true, 'email' => $email, 'ip' => $ipAddress, 'reason' => $reason]
        );

        return $this->respondCreated(['data' => $entry]);
    }

    public function delete($id = null)
    {
        $model = new BlacklistModel();
        $entry = $model->find($id);

        if (!$entry) {
            return $this->failNotFound('Blacklist entry not found.');
        }

        $model->delete($id);

        (new AdminHistory())->logUserUpdate(
            $this->request,
            $this->request->user['id'] ?? null,
            'system',
            null,
            ['blacklist_removed' => true, 'email' => $entry['email'], 'ip' => $entry['ip_address']]
        );

        return $this->respondDeleted(['message' => 'Blacklist entry removed.']);
    }
}
