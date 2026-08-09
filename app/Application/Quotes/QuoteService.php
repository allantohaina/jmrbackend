<?php

namespace App\Application\Quotes;

use App\Application\Shared\Result;
use App\Application\Notifications\NotificationService;
use App\Models\QuoteModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\IncomingRequest;

class QuoteService
{
    public function create(array $data, IncomingRequest $request): Result
    {
        $model = new QuoteModel();

        // Handle file uploads
        $files = $request->getFiles();
        $uploadedFiles = [];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/csv', 'application/csv'];
        $attachments = $files['technical_files'] ?? $files['technical_files[]'] ?? [];
        $attachments = is_array($attachments) ? $attachments : [$attachments];
        foreach ($attachments as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) continue;
            if ($file->getSize() > 10 * 1024 * 1024 || !in_array($file->getMimeType(), $allowedMimeTypes, true)) {
                return Result::fail(['message' => 'Format de fichier non autorisé ou fichier trop volumineux.'], 422);
            }
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);
            $uploadedFiles[] = [
                'name' => $file->getName(),
                'url' => base_url('uploads/' . $newName),
                'type' => $file->getMimeType(),
            ];
        }

        $quoteData = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'category' => $data['category'] ?? null,
            'tissu' => $data['tissu'] ?? null,
            'coupe' => $data['coupe'] ?? null,
            'gabarit' => $data['gabarit'] ?? null,
            'style' => $data['style'] ?? null,
            'grammage' => $data['grammage'] ?? null,
            'tailles' => $data['tailles'] ?? null,
            'quantite' => $data['quantite'] ?? null,
            'finitions' => $data['finitions'] ?? null,
            'delai_souhaite' => $data['delai_souhaite'] ?? null,
            'request_type' => $data['request_type'] ?? 'new',
            'modify_code' => $data['modify_code'] ?? null,
            'files' => !empty($uploadedFiles) ? json_encode($uploadedFiles) : null,
        ];

        if (!$model->save($quoteData)) {
            return Result::fail($model->errors(), 400);
        }

        $quoteId = $model->getInsertID();
        $quote = $model->getQuoteById($quoteId);

        $admins = (new UserModel())->where('role', 'admin')->findAll();
        $notifications = new NotificationService();
        foreach ($admins as $admin) {
            $notifications->create(
                $admin['id'], 'quote.requested', 'Nouvelle demande de devis',
                sprintf('%s a envoyé une demande de devis.', $quote['name'] ?? 'Un client'),
                'quote', $quoteId, '/backoffice/devis', null, 'info'
            );
        }

        return Result::created($quote);
    }

    public function list(IncomingRequest $request, ?string $userId = null): Result
    {
        $model = new QuoteModel();

        if ($userId) {
            $quotes = $model->where('email', $request->user['email'] ?? '')->orderBy('created_at', 'DESC')->findAll();
            $total = count($quotes);
            return Result::ok([
                'data' => $quotes,
                'total' => $total,
            ]);
        }

        $page = max(1, (int) ($request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($request->getGet('per_page') ?? 50)));
        $offset = ($page - 1) * $perPage;

        $quotes = $model->getAllQuotes($perPage, $offset);
        $total = $model->countAll();

        return Result::ok([
            'data' => $quotes,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function updateStatus(int|string $id, ?string $status, array $additionalData = [], array $actor = []): Result
    {
        $model = new QuoteModel();
        $quote = $model->getQuoteById($id);

        if (!$quote) {
            return Result::notFound('Quote not found');
        }

        $updateData = [];
        if ($status) {
            $updateData['status'] = $status;
        }
        foreach ($additionalData as $key => $value) {
            if (in_array($key, ['amount', 'deposit_amount', 'balance_amount', 'deposit_paid', 'balance_paid'])) {
                $updateData[$key] = $value;
            }
        }

        if (!$model->update($id, $updateData)) {
            return Result::fail($model->errors(), 400);
        }

        $updatedQuote = $model->getQuoteById($id);
        $client = (new UserModel())->where('email', $updatedQuote['email'] ?? '')->first();
        if ($client && $status) {
            $labels = [
                'sent' => ['Votre devis est prêt', 'Votre devis est disponible. Vous pouvez le consulter et le confirmer.', 'success'],
                'accepted' => ['Devis confirmé', 'Votre devis a été confirmé. Vous pouvez passer à la première tranche de paiement.', 'success'],
                'rejected' => ['Mise à jour du devis', 'Le devis a été mis à jour par notre équipe.', 'warning'],
            ];
            if (isset($labels[$status])) {
                [$title, $message, $type] = $labels[$status];
                (new NotificationService())->create($client['id'], 'quote.' . $status, $title, $message, 'quote', (string) $id, '/mon-profil', $actor['id'] ?? null, $type);
            }
        }
        return Result::ok($updatedQuote);
    }

    public function getQuoteById(string $id): ?array
    {
        $model = new QuoteModel();
        return $model->getQuoteById($id);
    }

    public function confirmByClient(int|string $id, array $actor): Result
    {
        $quote = (new QuoteModel())->getQuoteById($id);
        if (!$quote) return Result::notFound('Devis introuvable');
        if (($actor['role'] ?? null) !== 'user' || strtolower((string) ($actor['email'] ?? '')) !== strtolower((string) ($quote['email'] ?? ''))) {
            return Result::forbidden('Vous ne pouvez pas confirmer ce devis.');
        }
        if (($quote['status'] ?? null) !== 'sent') {
            return Result::fail(['message' => 'Ce devis ne peut pas être confirmé dans son état actuel.'], 422);
        }

        $model = new QuoteModel();
        if (!$model->update($id, ['status' => 'accepted'])) return Result::fail($model->errors(), 400);

        $notifications = new NotificationService();
        foreach ((new UserModel())->where('role', 'admin')->findAll() as $admin) {
            $notifications->create(
                $admin['id'], 'quote.accepted', 'Devis confirmé par le client',
                sprintf('%s a confirmé le devis.', $quote['name'] ?? 'Le client'),
                'quote', (string) $id, '/backoffice/devis', $actor['id'] ?? null, 'success'
            );
        }

        return Result::ok($model->getQuoteById($id));
    }

    public function getNotifications(?string $userId): array
    {
        // For now, return empty array, can be expanded later
        return [];
    }

    public function markAsRead(int|string $id): Result
    {
        // For now, return success, can be expanded later
        return Result::ok(['message' => 'Notification marked as read']);
    }
}
