<?php

namespace App\Application\Quotes;

use App\Application\Shared\Result;
use App\Models\QuoteModel;
use CodeIgniter\HTTP\IncomingRequest;

class QuoteService
{
    public function create(array $data, IncomingRequest $request): Result
    {
        $model = new QuoteModel();

        // Handle file uploads
        $files = $request->getFiles();
        $uploadedFiles = [];
        if (!empty($files)) {
            foreach ($files as $fileKey => $file) {
                if (is_array($file)) {
                    foreach ($file as $f) {
                        if ($f->isValid() && !$f->hasMoved()) {
                            $newName = $f->getRandomName();
                            $f->move(WRITEPATH . 'uploads', $newName);
                            $uploadedFiles[] = [
                                'name' => $f->getName(),
                                'url' => base_url('uploads/' . $newName),
                                'type' => $f->getMimeType(),
                            ];
                        }
                    }
                }
            }
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

        return Result::created($quote);
    }

    public function list(IncomingRequest $request): Result
    {
        $model = new QuoteModel();
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

    public function updateStatus(int|string $id, ?string $status, array $additionalData = []): Result
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
        return Result::ok($updatedQuote);
    }

    public function getQuoteById(string $id): ?array
    {
        $model = new QuoteModel();
        return $model->getQuoteById($id);
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
