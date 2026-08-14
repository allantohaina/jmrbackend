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
            unset($input['altcha']);

            $userId = $this->request->user['id'] ?? null;
            if ($userId) {
                $input['client_id'] = $userId;
            }

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

    public function update($id = null): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $status = $input['status'] ?? null;
            $additionalData = array_filter($input, fn($key) => !in_array($key, ['status']), ARRAY_FILTER_USE_KEY);

            $actor = $this->request->user ?? [];
            $quote = (new \App\Models\QuoteModel())->getQuoteById((string)$id);
            if (!$quote) {
                return $this->failNotFound('Devis introuvable');
            }

            $isAdmin = ($actor['role'] ?? null) === 'admin';
            $isOwner = !empty($quote['client_id']) && (string)$quote['client_id'] === (string)($actor['id'] ?? '');
            if (!$isOwner) {
                $isOwner = !empty($quote['email'])
                    && !empty($actor['email'])
                    && strtolower((string)$quote['email']) === strtolower((string)$actor['email']);
            }
            if (!$isAdmin && !$isOwner) {
                return $this->failForbidden('Vous ne pouvez pas modifier ce devis.');
            }

            // A client peut uniquement envoyer / accepter / refuser son propre devis,
            // ou enregistrer / mettre à jour un brouillon (status 'draft').
            if (!$isAdmin && $isOwner && $status && !in_array($status, ['pending', 'accepted', 'rejected', 'draft'], true)) {
                return $this->failForbidden('Transition de statut non autorisée pour ce devis.');
            }

            $result = $this->quoteService()->updateStatus($id, $status, $additionalData, $actor);

            if ($result->isSuccess()) {
                return $this->respond($result->getPayload());
            }

            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Quotes update error: ' . $e->getMessage());
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

    public function convertToCommande($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut créer une commande.');
            }
            $result = $this->quoteService()->convertToCommande((string)$id);
            if (!$result->isSuccess()) {
                return $this->fail($result->getPayload(), $result->getStatus());
            }
            return $this->respondCreated($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'Quotes convertToCommande error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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

    public function notify($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut envoyer une notification au client.');
            }

            $quoteModel = new \App\Models\QuoteModel();
            $quote = $quoteModel->getQuoteById((string)$id);
            if (!$quote) {
                return $this->failNotFound('Devis introuvable.');
            }

            $input = $this->getInputData();
            $type = (string)($input['type'] ?? '');

            $labels = [
                'delay' => ['Retard de production', 'Votre commande accuse un retard de production. Notre équipe vous informe dès que possible.', 'warning'],
                'error' => ['Erreur de conception / technique', 'Une erreur a été détectée sur votre commande. Nous vous contactons rapidement pour la résoudre.', 'error'],
                'ready' => ['Prêt pour livraison', 'Votre commande est prête pour la livraison. Vous pouvez en suivre l\'avancement dans votre espace client.', 'success'],
                'info' => ['Besoin d\'informations complémentaires', 'Nous avons besoin d\'informations complémentaires pour finaliser votre commande.', 'info'],
            ];

            if (!isset($labels[$type])) {
                return $this->failValidationErrors(['type' => 'Type d\'alerte invalide.']);
            }

            $userModel = new \App\Models\UserModel();
            $client = null;
            if (!empty($quote['client_id'])) {
                $client = $userModel->find($quote['client_id']);
            }
            if (!$client && !empty($quote['email'])) {
                $client = $userModel->where('email', $quote['email'])->first();
            }
            if (!$client) {
                return $this->failNotFound('Aucun compte client associé à ce devis.');
            }

            [$title, $message, $notificationType] = $labels[$type];
            (new \App\Application\Notifications\NotificationService())->create(
                $client['id'],
                'quote.' . $type,
                $title,
                $message,
                'quote',
                (string)$id,
                '/mon-profil',
                $actor['id'] ?? null,
                $notificationType,
            );

            return $this->respondCreated(['message' => 'Notification envoyée au client.']);
        } catch (Throwable $e) {
            log_message('error', 'Quotes notify error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function sign($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut signer un document.');
            }

            $input = $this->getInputData();
            $signatureName = trim((string)($input['admin_signature_name'] ?? ''));
            $signatureAt = $input['admin_signature_at'] ?? date('Y-m-d H:i:s');

            if ($signatureName === '') {
                return $this->failValidationErrors(['admin_signature_name' => 'Le nom du signataire est requis.']);
            }

            $quoteModel = new \App\Models\QuoteModel();
            $quote = $quoteModel->find($id);
            if (!$quote) {
                return $this->failNotFound('Devis introuvable.');
            }

            $updated = $quoteModel->update($id, [
                'admin_signature_name' => $signatureName,
                'admin_signature_at'   => is_string($signatureAt) ? $signatureAt : date('Y-m-d H:i:s'),
            ]);

            if (!$updated) {
                return $this->failServerError('Impossible d\'enregistrer la signature.');
            }

            $fresh = $quoteModel->find($id);
            return $this->respond($fresh);
        } catch (Throwable $e) {
            log_message('error', 'Quotes sign error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function remove($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            $quoteModel = new \App\Models\QuoteModel();
            $quote = $quoteModel->getQuoteById((string)$id);
            if (!$quote) {
                return $this->failNotFound('Devis introuvable.');
            }

            $isAdmin = ($actor['role'] ?? null) === 'admin';
            $isOwner = !empty($quote['client_id']) && (string)$quote['client_id'] === (string)($actor['id'] ?? '');
            if (!$isOwner) {
                $isOwner = !empty($quote['email'])
                    && !empty($actor['email'])
                    && strtolower((string)$quote['email']) === strtolower((string)$actor['email']);
            }
            if (!$isAdmin && !$isOwner) {
                return $this->failForbidden('Vous ne pouvez pas supprimer ce devis.');
            }

            // Seul un brouillon non envoyé peut être supprimé (ou un admin).
            if (!$isAdmin && ($quote['status'] ?? '') !== 'draft') {
                return $this->failForbidden('Seul un brouillon peut être supprimé.');
            }

            // Un brouillon non envoyé peut être supprimé par son propriétaire.
            $quoteModel->delete($id);

            return $this->respondDeleted(['message' => 'Devis supprimé.']);
        } catch (Throwable $e) {
            log_message('error', 'Quotes remove error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
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
