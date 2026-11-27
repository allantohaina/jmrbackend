<?php

namespace App\Application\Payments;

use App\Application\Shared\Result;
use App\Models\PaymentModel;

class PaymentService
{
    private PaymentModel $model;

    /** Notification générique liée à un paiement et à son devis. */
    private function notifyPayment(string $quoteId, string $event, string $title, string $message, string $type, string $url, ?string $actorId = null, bool $toStaff = false): void
    {
        try {
            $notifications = new \App\Application\Notifications\NotificationService();
            $userModel = new \App\Models\UserModel();
            $quote = (new \App\Models\QuoteModel())->getQuoteById($quoteId);
            if (!$quote) return;

            if ($toStaff) {
                foreach ($userModel->whereIn('role', ['admin', 'worker'])->findAll() as $member) {
                    $notifications->create((string)$member['id'], $event, $title, $message, 'payment', $quoteId, $url, $actorId, $type);
                }
                return;
            }

            $client = null;
            if (!empty($quote['client_id'])) $client = $userModel->find($quote['client_id']);
            if (!$client && !empty($quote['email'])) {
                $row = \Config\Database::connect()
                    ->query('SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1', [strtolower((string)$quote['email'])])
                    ->getRowArray();
                if ($row) $client = $row;
            }
            if ($client) {
                $notifications->create((string)$client['id'], $event, $title, $message, 'payment', $quoteId, $url, $actorId, $type);
            }
        } catch (\Throwable $e) {
            log_message('error', 'PaymentService notifyPayment: ' . $e->getMessage());
        }
    }

    public function __construct(?PaymentModel $model = null)
    {
        $this->model = $model ?? new PaymentModel();
    }

    public function listByQuote(string $quoteId): Result
    {
        $rows = $this->model->where('quote_id', $quoteId)->orderBy('created_at', 'ASC')->findAll();
        $total = 0;
        foreach ($rows as $row) {
            if (($row['status'] ?? null) === 'verified') {
                $total += (float)($row['amount'] ?? 0);
            }
        }
        return Result::ok(['data' => $rows, 'total_verified' => $total]);
    }

    /** Preuves en attente (submitted avec preuve) pour validation admin. */
    public function listPending(): Result
    {
        $rows = $this->model
            ->select('payments.*, quotes.name as client_name, quotes.email as client_email, quotes.amount as quote_amount, quotes.status as quote_status')
            ->join('quotes', 'quotes.id = payments.quote_id', 'left')
            ->where('payments.status', 'submitted')
            ->where('payments.proof_path IS NOT NULL')
            ->orderBy('payments.created_at', 'DESC')
            ->findAll(100);
        return Result::ok(['data' => $rows]);
    }

    public function getById(string $id): Result
    {
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Paiement introuvable.');
        return Result::ok(['data' => $row]);
    }

    public function submitForQuote(string $quoteId, array $data, ?string $actorId = null): Result
    {
        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->find($quoteId);
        if (!$quote) return Result::notFound('Devis introuvable.');

        // Aucune transaction possible avant validation client (accepted/production).
        if (!in_array($quote['status'] ?? '', ['accepted', 'production', 'completed'], true)) {
            return Result::fail(['error' => 'Le devis doit d’abord être validé par le client avant tout paiement.'], 422);
        }

        // Preuve image/PDF obligatoire (le client doit prouver son paiement).
        if (empty($data['proof_path'])) {
            return Result::fail(['error' => 'La preuve de paiement (image/PDF) est obligatoire.'], 422);
        }

        $paymentModel = $this->model;
        // Une tranche rejetée peut être resoumise avec une nouvelle preuve.
        $payment = $paymentModel->where('quote_id', $quoteId)
            ->whereIn('status', ['submitted', 'rejected'])
            ->orderBy('created_at', 'ASC')
            ->first();
        if (!$payment) {
            // Filet de sécurité : si la tranche 1 n'a pas été créée lors de l'acceptation
            // (écriture concurrente / retard), on la crée ici avant de continuer.
            $amount = (float)($quote['amount'] ?? 0);
            if ($amount > 0) {
                $paymentModel->insert([
                    'quote_id' => $quoteId,
                    'phase' => 'deposit',
                    'amount' => round($amount * 0.50, 2),
                    'status' => 'submitted',
                ]);
                $payment = $paymentModel->where('quote_id', $quoteId)
                    ->where('phase', 'deposit')
                    ->where('status', 'submitted')
                    ->first();
            }
        }
        if (!$payment) {
            return Result::fail(['error' => 'Aucune tranche de paiement en attente pour ce devis.'], 422);
        }

        // Si une preuve a déjà été envoyée, on attend la validation admin (pas d'écrasement).
        // Exception : une tranche rejetée peut être resoumise avec une nouvelle preuve.
        if (!empty($payment['proof_path']) && ($payment['status'] ?? '') === 'submitted') {
            return Result::fail(['error' => 'Preuve déjà envoyée pour cette tranche — en attente de vérification par l’atelier.'], 422);
        }

        // La tranche 2 (solde) n'est payable qu'après vérification de la tranche 1.
        if (($payment['phase'] ?? '') === 'balance') {
            $deposit = $paymentModel->where('quote_id', $quoteId)->where('phase', 'deposit')->first();
            if (($deposit['status'] ?? '') !== 'verified') {
                return Result::fail(['error' => 'La tranche 1 (acompte) doit être vérifiée avant de payer le solde.'], 422);
            }
        }

        $updateData = [
            'proof_path' => $data['proof_path'] ?? null,
            'submitted_by' => $actorId,
            'status' => 'submitted',
            'reviewed_at' => null,
        ];
        if (!empty($data['payment_type'])) $updateData['payment_type'] = $data['payment_type'];
        if (!empty($data['transaction_ref'])) $updateData['transaction_ref'] = $data['transaction_ref'];

        if (!$paymentModel->update($payment['id'], $updateData)) {
            return Result::fail(['error' => 'Erreur lors de l\'enregistrement de la preuve.', 'messages' => $paymentModel->errors()], 422);
        }

        $this->notifyPayment(
            $quoteId, 'payment.submitted', 'Preuve de paiement envoyée',
            sprintf('Une preuve de paiement (%s) attend votre vérification.', $updateData['payment_type'] ?? 'virement'),
            'info', '/backoffice/devis/edit?id=' . $quoteId, $actorId, true
        );

        return Result::ok(['data' => $paymentModel->find($payment['id'])]);
    }

    public function updateStatus(string $id, string $status, ?string $reviewNote = null, ?string $reviewedBy = null): Result
    {
        if (!in_array($status, ['verified', 'rejected'], true)) {
            return Result::fail(['error' => 'Statut invalide : seul « verified » ou « rejected » est autorisé.'], 422);
        }
        $row = $this->model->find($id);
        if (!$row) return Result::notFound('Paiement introuvable.');

        $updateData = [
            'status' => $status,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];
        if ($reviewNote !== null) $updateData['review_note'] = $reviewNote;
        if ($reviewedBy !== null) $updateData['reviewed_by'] = $reviewedBy;

        if (!$this->model->update($id, $updateData)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.'], 422);
        }

        // If verified, update the quote's deposit_paid or balance_paid
        if ($status === 'verified' && !empty($row['quote_id'])) {
            $this->markQuotePaymentAsPaid($row['quote_id'], $row['phase']);
            $label = ($row['phase'] ?? '') === 'deposit' ? 'acompte' : 'solde';
            $this->notifyPayment(
                (string)$row['quote_id'], 'payment.verified', 'Paiement vérifié',
                'Votre ' . $label . ' a été vérifié par l’atelier. Merci pour votre confiance.',
                'success', '/mon-profil/devis/detail?id=' . $row['quote_id'], $reviewedBy
            );
        } elseif ($status === 'rejected' && !empty($row['quote_id'])) {
            $this->notifyPayment(
                (string)$row['quote_id'], 'payment.rejected', 'Preuve de paiement refusée',
                'Votre preuve de paiement a été refusée. ' . (string)($reviewNote ?? 'Merci de soumettre une nouvelle preuve.'),
                'warning', '/mon-profil/devis/detail?id=' . $row['quote_id'], $reviewedBy
            );
        }

        return Result::ok(['data' => $this->model->find($id)]);
    }

    private function markQuotePaymentAsPaid(string $quoteId, string $phase): void
    {
        $quoteModel = new \App\Models\QuoteModel();
        $update = [];
        if ($phase === 'deposit') {
            $update['deposit_paid'] = true;
            $update['deposit_paid_at'] = date('Y-m-d H:i:s');
        } elseif ($phase === 'balance') {
            $update['balance_paid'] = true;
            $update['balance_paid_at'] = date('Y-m-d H:i:s');
        }
        if (!empty($update)) {
            $quoteModel->update($quoteId, $update);
        }

        // Auto-create tranche 2 (balance) when tranche 1 (deposit) is verified
        if ($phase === 'deposit') {
            $this->createTranche2IfMissing($quoteId);
            // La 1ère tranche validée démarre la production.
            $q = $quoteModel->find($quoteId);
            if ($q && ($q['status'] ?? '') === 'accepted') {
                $quoteModel->update($quoteId, ['status' => 'production']);
                (new \App\Application\Quotes\QuoteService())->notifyQuoteStatus($q, 'production', null, 'accepted');
            }
        }
    }

    private function createTranche2IfMissing(string $quoteId): void
    {
        $existing = $this->model->where('quote_id', $quoteId)
            ->where('phase', 'balance')
            ->first();
        if ($existing) return;

        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->find($quoteId);
        if (!$quote) return;

        $depositAmount = (float)($quote['deposit_amount'] ?? 0);
        $totalAmount = (float)($quote['amount'] ?? 0);
        $balanceAmount = (float)($quote['balance_amount'] ?? 0);

        // Balance = total - deposit + any addon additions to balance_amount
        $balance = $balanceAmount > 0 ? $balanceAmount : ($totalAmount - $depositAmount);
        if ($balance <= 0) return;

        $this->model->insert([
            'quote_id' => $quoteId,
            'phase' => 'balance',
            'amount' => $balance,
            'status' => 'submitted',
        ]);

        $quoteModel->update($quoteId, [
            'balance_amount' => $balance,
            'balance_paid' => false,
        ]);
    }

    public function syncTranche2Amount(string $quoteId): void
    {
        $quoteModel = new \App\Models\QuoteModel();
        $quote = $quoteModel->find($quoteId);
        if (!$quote) return;

        $totalAmount = (float)($quote['amount'] ?? 0);
        $depositAmount = (float)($quote['deposit_amount'] ?? 0);
        $balanceAmount = (float)($quote['balance_amount'] ?? 0);

        $newBalance = $balanceAmount > 0 ? $balanceAmount : ($totalAmount - $depositAmount);

        $existing = $this->model->where('quote_id', $quoteId)
            ->where('phase', 'balance')
            ->first();

        if ($existing && (float)($existing['amount'] ?? 0) !== $newBalance && $existing['status'] !== 'verified') {
            $this->model->update($existing['id'], ['amount' => $newBalance]);
        }
    }
}
