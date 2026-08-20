<?php

namespace App\Application\Suivi;

use App\Application\Shared\Result;
use App\Models\CommandeModel;
use App\Models\UserModel;

class SuiviCommandeService
{
    public function lookup(string $numero, string $email): Result
    {
        $numero = trim($numero);
        $email = strtolower(trim($email));
        if ($numero === '' || $email === '') {
            return Result::fail(['error' => 'Numéro de commande et email requis.'], 422);
        }

        $commande = (new CommandeModel())->where('numero', $numero)->first();
        if (!$commande) {
            return Result::notFound('Aucune commande trouvée avec ce numéro.');
        }

        $emailOk = false;
        if (!empty($commande['client_id'])) {
            $client = (new UserModel())->find($commande['client_id']);
            if ($client && strtolower((string)$client['email']) === $email) {
                $emailOk = true;
            }
        }
        if (!$emailOk) {
            $commandeWithClient = (new CommandeModel())->getCommandeWithClient($commande['id']);
            if ($commandeWithClient && strtolower((string)($commandeWithClient['client_email'] ?? '')) === $email) {
                $emailOk = true;
            }
        }
        if (!$emailOk) {
            return Result::notFound('Aucune commande trouvée pour cet email.');
        }

        $today = date('Y-m-d');
        $estLivree = ($commande['statut_production'] ?? '') === 'Livrée';
        $prevue = $commande['date_livraison_prevue'] ?? null;

        return Result::ok([
            'data' => [
                'numero' => $commande['numero'],
                'designation' => $commande['designation'] ?? '',
                'quantite' => $commande['quantite'] ?? 0,
                'statut_production' => $commande['statut_production'] ?? '',
                'pieces_produites' => $commande['pieces_produites'] ?? 0,
                'date_commande' => $commande['date_commande'] ?? null,
                'date_livraison_prevue' => $prevue,
                'date_livraison_reelle' => $commande['date_livraison_reelle'] ?? null,
                'en_retard' => !$estLivree && $prevue && $prevue < $today,
            ],
        ]);
    }
}