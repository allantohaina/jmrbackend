<?php

namespace App\Controllers;

use App\Application\Notifications\PushService;
use App\Models\PushSubscriptionModel;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class PushSubscriptions extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $userId = $this->request->user['id'] ?? null;
        if (!$userId) return $this->failUnauthorized();

        $subscriptions = (new PushSubscriptionModel())->where('user_id', $userId)->findAll();
        return $this->respond(['data' => $subscriptions]);
    }

    public function create()
    {
        $userId = $this->request->user['id'] ?? null;
        if (!$userId) return $this->failUnauthorized();

        $endpoint = $this->request->getPost('endpoint');
        $keys = $this->request->getPost('keys');

        if (!is_string($endpoint) || $endpoint === '') {
            return $this->fail('L\'endpoint de la souscription est requis.', 400);
        }

        if (!is_array($keys) || empty($keys['p256dh']) || empty($keys['auth'])) {
            return $this->fail('Les clés de souscription sont requises.', 400);
        }

        $model = new PushSubscriptionModel();

        try {
            $existing = $model->where('user_id', $userId)->where('endpoint', $endpoint)->first();
            if ($existing) {
                return $this->respond(['data' => $existing, 'message' => 'Souscription déjà enregistrée.']);
            }

            $id = $model->insert([
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'keys_p256dh' => $keys['p256dh'],
                'keys_auth' => $keys['auth'],
            ]);

            return $this->respondCreated(['data' => $model->find($id)]);
        } catch (Throwable $e) {
            log_message('error', 'Push subscription could not be saved: ' . $e->getMessage());
            return $this->fail('Impossible d\'enregistrer la souscription.', 500);
        }
    }

    public function remove($id = null)
    {
        $userId = $this->request->user['id'] ?? null;
        if (!$userId) return $this->failUnauthorized();

        $model = new PushSubscriptionModel();
        $subscription = $model->where('id', $id)->where('user_id', $userId)->first();
        if (!$subscription) return $this->failNotFound('Souscription introuvable.');

        $model->delete($id);
        return $this->respond(['message' => 'Souscription supprimée.']);
    }

    public function test()
    {
        $userId = $this->request->user['id'] ?? null;
        if (!$userId) return $this->failUnauthorized();

        $service = new PushService();
        try {
            $service->sendToUser($userId, 'Test JMR Textile', 'Vous recevez maintenant vos notifications même hors du site. 🎉', '/mon-profil');
            return $this->respond(['message' => 'Notification de test envoyée.']);
        } catch (Throwable $e) {
            log_message('error', 'Push test failed: ' . $e->getMessage());
            return $this->fail('L\'envoi de la notification de test a échoué.', 500);
        }
    }
}