<?php

namespace App\Application\Paiement;

use App\Application\Shared\Result;
use App\Application\Payments\PaymentService;
use App\Models\CommandeModel;
use App\Models\LienPaiementModel;
use App\Models\QuoteModel;
use App\Models\UserModel;
use App\Application\Fidelite\PointFideliteService;

class LienPaiementService
{
    public function generate(string $commandeId, array $data, ?string $actorId = null): Result
    {
        $commande = (new CommandeModel())->find($commandeId);
        if (!$commande) return Result::notFound('Commande introuvable.');

        $montant = (float)($data['montant'] ?? $commande['total'] ?? 0);
        if ($montant <= 0) {
            return Result::fail(['error' => 'Montant du lien de paiement invalide.'], 422);
        }

        $token = bin2hex(random_bytes(16));
        $expireAt = $data['expire_at'] ?? date('Y-m-d H:i:s', time() + (7 * 86400));

        $lien = [
            'commande_id' => $commandeId,
            'token' => $token,
            'montant' => $montant,
            'statut' => 'pending',
            'expire_at' => $expireAt,
        ];
        $model = new LienPaiementModel();
        if (!$model->insert($lien)) {
            return Result::fail(['error' => 'Erreur lors de la génération du lien.', 'messages' => $model->errors()], 422);
        }
        $lien = $model->find($model->getInsertID());
        $frontendUrl = rtrim((string) getenv('FRONTEND_URL'), '/');
        $lien['url'] = ($frontendUrl !== '' ? $frontendUrl : base_url()) . '/paiement?token=' . $token;

        return Result::created(['data' => $lien]);
    }

    public function getByToken(string $token): Result
    {
        $lien = (new LienPaiementModel())->where('token', $token)->first();
        if (!$lien) return Result::notFound('Lien de paiement introuvable.');
        if (($lien['statut'] ?? '') === 'paid') {
            return Result::ok(['data' => $this->decorate($lien, 'déjà payé')]);
        }
        if (!empty($lien['expire_at']) && strtotime($lien['expire_at']) < time()) {
            return Result::ok(['data' => $this->decorate($lien, 'expiré')]);
        }
        return Result::ok(['data' => $this->decorate($lien, 'valide')]);
    }

    public function pay(string $token): Result
    {
        $model = new LienPaiementModel();
        $lien = $model->where('token', $token)->first();
        if (!$lien) return Result::notFound('Lien de paiement introuvable.');
        if (($lien['statut'] ?? '') === 'paid') {
            return Result::fail(['error' => 'Ce lien a déjà été payé.'], 422);
        }
        if (!empty($lien['expire_at']) && strtotime($lien['expire_at']) < time()) {
            return Result::fail(['error' => 'Ce lien de paiement est expiré.'], 410);
        }

        $commande = (new CommandeModel())->find($lien['commande_id']);
        if (!$commande) return Result::notFound('Commande introuvable.');

        $quote = null;
        if (!empty($commande['cotation_id'])) {
            $quote = (new QuoteModel())->find($commande['cotation_id']);
        }

        $phase = 'balance';
        if ($quote) {
            $phase = !empty($quote['deposit_paid']) ? 'balance' : 'deposit';
        } elseif (($commande['statut_production'] ?? '') === 'Livrée') {
            $phase = 'balance';
        }

        $paymentService = new PaymentService();
        $paymentModel = new \App\Models\PaymentModel();
        $paymentModel->skipValidation(true);
        $paymentId = \App\Traits\UuidTrait::uuidV4();
        $inserted = $paymentModel->insert([
            'id' => $paymentId,
            'quote_id' => $quote['id'] ?? null,
            'commande_id' => $commande['id'],
            'phase' => $phase,
            'amount' => $lien['montant'],
            'status' => 'verified',
            'payment_type' => 'lien',
            'transaction_ref' => 'LIEN-' . strtoupper(substr($lien['token'], 0, 12)),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$inserted) {
            return Result::fail(['error' => 'Erreur lors de l\'enregistrement du paiement.', 'messages' => $paymentModel->errors()], 422);
        }

        $model->update($lien['id'], ['statut' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);

        if ($quote) {
            $update = [];
            if ($phase === 'deposit') {
                $update['deposit_paid'] = true;
                $update['deposit_paid_at'] = date('Y-m-d H:i:s');
            } else {
                $update['balance_paid'] = true;
                $update['balance_paid_at'] = date('Y-m-d H:i:s');
            }
            (new QuoteModel())->update($quote['id'], $update);
        }

        $client = null;
        if (!empty($commande['client_id'])) {
            $client = (new UserModel())->find($commande['client_id']);
        }
        if ($client) {
            try {
                (new PointFideliteService())->award(
                    $client['id'],
                    (int) floor((float) $lien['montant']),
                    'Paiement commande ' . ($commande['numero'] ?? ''),
                    'payment',
                    (string) $paymentId
                );
            } catch (\Throwable) {
            }
        }

        return Result::created([
            'data' => $this->decorate($model->find($lien['id']), 'payé'),
            'paiement_id' => $paymentId,
            'message' => 'Paiement enregistré avec succès. Merci !',
        ]);
    }

    private function decorate(array $lien, string $etat): array
    {
        $commande = (new CommandeModel())->getCommandeWithClient($lien['commande_id']);
        $clientNom = '';
        if ($commande) {
            $clientNom = trim(($commande['client_first_name'] ?? '') . ' ' . ($commande['client_last_name'] ?? ''));
        }
        $lien['commande_numero'] = $commande['numero'] ?? '';
        $lien['commande_designation'] = $commande['designation'] ?? '';
        $lien['client_nom'] = $clientNom ?: ($commande['client_email'] ?? '');
        $lien['etat'] = $etat;
        unset($lien['commande_id']);
        return $lien;
    }
}