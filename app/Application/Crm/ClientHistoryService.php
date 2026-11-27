<?php

namespace App\Application\Crm;

use App\Application\Shared\Result;
use App\Models\CommandeModel;
use App\Models\PaymentModel;
use App\Models\QuoteAddonModel;
use App\Models\QuoteModel;
use App\Models\UserModel;

/** Historique client consolidé : devis, commandes, paiements et éléments ajoutés. */
class ClientHistoryService
{
    public function getForUser(string $userId): Result
    {
        $user = (new UserModel())->find($userId);
        if (!$user) return Result::notFound('Client introuvable.');

        $quotes = (new QuoteModel())->groupStart()
            ->where('client_id', $userId)
            ->orWhere('email', $user['email'] ?? '')
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $quoteIds = array_map(static fn($q) => (string)$q['id'], $quotes);
        $items = [];
        $total = 0.0;
        $paidTotal = 0.0;

        foreach ($quotes as $q) {
            $amount = (float)($q['amount'] ?? $q['prix_total_calcule'] ?? 0);
            $total += $amount;
            $items[] = [
                'type' => 'devis',
                'id' => $q['id'],
                'date' => $q['created_at'] ?? null,
                'label' => 'Demande de devis',
                'detail' => (string)($q['titre'] ?? $q['name'] ?? 'Devis'),
                'amount' => $amount,
                'status' => $q['status'] ?? '',
                'action_url' => '/mon-profil/devis/detail?id=' . $q['id'],
            ];
        }

        if ($quoteIds) {
            $payments = (new PaymentModel())->whereIn('quote_id', $quoteIds)->findAll();
            foreach ($payments as $p) {
                if (($p['status'] ?? '') !== 'verified') continue;
                $amount = (float)($p['amount'] ?? 0);
                $paidTotal += $amount;
                $items[] = [
                    'type' => 'paiement',
                    'id' => $p['id'],
                    'date' => $p['reviewed_at'] ?? $p['created_at'] ?? null,
                    'label' => 'Paiement vérifié — ' . ucfirst((string)($p['phase'] ?? '')),
                    'detail' => (string)($p['transaction_ref'] ?? ''),
                    'amount' => $amount,
                    'status' => 'verified',
                    'action_url' => '/mon-profil/devis/detail?id=' . $p['quote_id'],
                ];
            }

            $addons = (new QuoteAddonModel())->whereIn('quote_id', $quoteIds)->findAll();
            foreach ($addons as $a) {
                if (($a['status'] ?? '') !== 'included') continue;
                $amount = (float)($a['price'] ?? 0);
                $total += $amount;
                $items[] = [
                    'type' => 'element',
                    'id' => $a['id'],
                    'date' => $a['created_at'] ?? null,
                    'label' => 'Élément ajouté',
                    'detail' => (string)($a['title'] ?? ''),
                    'amount' => $amount,
                    'status' => 'included',
                    'action_url' => '/mon-profil/devis/detail?id=' . $a['quote_id'],
                ];
            }
        }

        $commandes = (new CommandeModel())->where('client_id', $userId)->findAll();
        foreach ($commandes as $c) {
            $amount = (float)($c['total'] ?? 0);
            $items[] = [
                'type' => 'commande',
                'id' => $c['id'],
                'date' => $c['date_commande'] ?? $c['created_at'] ?? null,
                'label' => 'Commande',
                'detail' => (string)($c['numero'] ?? '') . ' — ' . (string)($c['designation'] ?? ''),
                'amount' => $amount,
                'status' => $c['statut_production'] ?? '',
                'action_url' => '/suivi-commande',
            ];
        }

        usort($items, static function ($a, $b) {
            return strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
        });

        return Result::ok([
            'user_id' => $userId,
            'items' => $items,
            'total' => round($total, 2),
            'paid_total' => round($paidTotal, 2),
        ]);
    }
}
