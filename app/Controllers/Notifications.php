<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\RESTful\ResourceController;

class Notifications extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $userId = $this->request->user['id'] ?? null;
        if (!$userId) return $this->failUnauthorized();

        $model = new NotificationModel();
        $unreadOnly = $this->request->getGet('unread_only') === 'true';
        $builder = $model->where('recipient_user_id', $userId)->orderBy('created_at', 'DESC');
        if ($unreadOnly) $builder->where('read_at', null);

        $notifications = $builder->findAll(50);
        $unreadCount = $model->where('recipient_user_id', $userId)->where('read_at', null)->countAllResults();
        return $this->respond(['data' => $notifications, 'unread_count' => $unreadCount]);
    }

    public function markRead($id = null)
    {
        $userId = $this->request->user['id'] ?? null;
        $model = new NotificationModel();
        $notification = $model->where('id', $id)->where('recipient_user_id', $userId)->first();
        if (!$notification) return $this->failNotFound('Notification introuvable.');

        $model->update($id, ['read_at' => date('Y-m-d H:i:s')]);
        return $this->respond(['data' => $model->find($id)]);
    }

    public function markAllRead()
    {
        $userId = $this->request->user['id'] ?? null;
        if (!$userId) return $this->failUnauthorized();

        (new NotificationModel())->where('recipient_user_id', $userId)->where('read_at', null)
            ->set(['read_at' => date('Y-m-d H:i:s')])->update();
        return $this->respond(['message' => 'Notifications marquées comme lues.']);
    }
}
