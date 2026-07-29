<?php

namespace App\Controllers;

use App\History\AdminHistory;
use App\Models\BanModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class Bans extends ResourceController
{
    public function index()
    {
        $model = new BanModel();
        $perPage = $this->request->getGet('per_page') ?? 20;
        $page = $this->request->getGet('page') ?? 1;

        $bans = $model->select('user_bans.*, u1.email as user_email, u1.first_name as user_first_name, u1.last_name as user_last_name, u2.email as banned_by_email')
            ->join('users u1', 'u1.id = user_bans.user_id', 'left')
            ->join('users u2', 'u2.id = user_bans.banned_by', 'left')
            ->orderBy('user_bans.created_at', 'DESC')
            ->paginate($perPage, 'default', $page);

        return $this->respond([
            'data' => $bans ?: [],
            'total' => $model->pager->getTotal(),
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function show($id = null)
    {
        $model = new BanModel();
        $ban = $model->select('user_bans.*, u1.email as user_email, u2.email as banned_by_email')
            ->join('users u1', 'u1.id = user_bans.user_id', 'left')
            ->join('users u2', 'u2.id = user_bans.banned_by', 'left')
            ->find($id);

        if (!$ban) {
            return $this->failNotFound('Ban not found.');
        }

        return $this->respond(['data' => $ban]);
    }

    public function create()
    {
        $json = $this->request->getJSON(true);
        $userId = $json['user_id'] ?? null;
        $reason = $json['reason'] ?? null;
        $expiresAt = $json['expires_at'] ?? null;

        if (!$userId || !$reason) {
            return $this->failValidationErrors('user_id and reason are required.');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (!$user) {
            return $this->failNotFound('User not found.');
        }

        $model = new BanModel();
        $data = [
            'user_id' => $userId,
            'banned_by' => $this->request->user['id'] ?? null,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (!$model->insert($data)) {
            return $this->failServerError('Failed to create ban.');
        }

        $banId = $model->getInsertID();
        $ban = $model->find($banId);

        (new AdminHistory())->logUserUpdate(
            $this->request,
            $this->request->user['id'] ?? null,
            $userId,
            null,
            ['banned' => true, 'reason' => $reason]
        );

        return $this->respondCreated(['data' => $ban]);
    }

    public function delete($id = null)
    {
        $model = new BanModel();
        $ban = $model->find($id);

        if (!$ban) {
            return $this->failNotFound('Ban not found.');
        }

        $model->delete($id);

        (new AdminHistory())->logUserUpdate(
            $this->request,
            $this->request->user['id'] ?? null,
            $ban['user_id'],
            null,
            ['unbanned' => true, 'previous_ban_id' => $id]
        );

        return $this->respondDeleted(['message' => 'Ban lifted.']);
    }

    public function userBans($userId = null)
    {
        $model = new BanModel();
        $bans = $model->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond(['data' => $bans ?: []]);
    }
}
