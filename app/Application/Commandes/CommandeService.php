<?php

namespace App\Application\Commandes;

use App\Application\Shared\Result;
use App\Models\CommandeModel;

class CommandeService
{
    private CommandeModel $model;

    public const STATUT_ORDER = [
        'En attente matière' => 0,
        'Coupe' => 1,
        'Couture' => 2,
        'Finition' => 3,
        'Prête' => 4,
        'Livrée' => 5,
    ];

    public function __construct()
    {
        $this->model = new CommandeModel();
    }

    public function list(?string $userId = null, ?string $role = null): Result
    {
        $isAdmin = ($role === 'admin');
        if ($userId && !$isAdmin) {
            $commandes = $this->model->where('client_id', $userId)->orderBy('created_at', 'DESC')->findAll();
        } else {
            $commandes = $this->model->getCommandesWithClient();
        }
        $today = date('Y-m-d');
        foreach ($commandes as &$c) {
            $estLivree = ($c['statut_production'] ?? '') === 'Livrée';
            $prevue = $c['date_livraison_prevue'] ?? null;
            $c['en_retard'] = !$estLivree && $prevue && $prevue < $today;
        }
        $counts = [];
        foreach (array_keys(self::STATUT_ORDER) as $s) {
            $counts[$s] = 0;
        }
        $counts['en_retard'] = 0;
        $caLivreMois = 0;
        $moisCourant = date('Y-m');
        foreach ($commandes as $c) {
            $s = $c['statut_production'] ?? '';
            if (isset($counts[$s])) $counts[$s]++;
            if (!empty($c['en_retard'])) $counts['en_retard']++;
            if ($s === 'Livrée' && ($c['date_livraison_reelle'] ?? false)) {
                if (substr((string)$c['date_livraison_reelle'], 0, 7) === $moisCourant) {
                    $caLivreMois += (float)($c['total'] ?? 0);
                }
            }
        }
        $counts['ca_mois'] = round($caLivreMois, 2);
        return Result::ok(['data' => $commandes, 'counts' => $counts]);
    }

    public function getById(string $id): Result
    {
        $commande = $this->model->getCommandeWithClient($id);
        if (!$commande) return Result::notFound('Commande introuvable.');
        $commande['en_retard'] = ($commande['statut_production'] ?? '') !== 'Livrée'
            && ($commande['date_livraison_prevue'] ?? null)
            && $commande['date_livraison_prevue'] < date('Y-m-d');
        return Result::ok(['data' => $commande]);
    }

    public function isStatutTransitionValid(string $from, string $to): bool
    {
        $fromIdx = self::STATUT_ORDER[$from] ?? null;
        $toIdx = self::STATUT_ORDER[$to] ?? null;
        if ($fromIdx === null || $toIdx === null) return false;
        if ($to === 'Livrée' && $from !== 'Prête') return false;
        return $toIdx >= $fromIdx;
    }

    public function create(array $data): Result
    {
        $data['date_commande'] = $data['date_commande'] ?? date('Y-m-d');
        $data['statut_production'] = $data['statut_production'] ?? 'En attente matière';
        $data['total'] = ($data['quantite'] ?? 0) * ($data['prix_unitaire'] ?? 0);

        // Calcul auto quantité tissu nécessaire
        $conso = (float)($data['conso_tissu_unitaire'] ?? 0);
        $tauxChute = (float)($data['taux_chute_pct'] ?? 10);
        $quantite = (int)($data['quantite'] ?? 0);
        if ($conso > 0 && $quantite > 0) {
            $tissuParPiece = $conso * (1 + ($tauxChute / 100));
            $data['quantite_tissu_necessaire'] = round($tissuParPiece * $quantite, 3);
        } else {
            $data['quantite_tissu_necessaire'] = 0;
        }

        if (!$this->model->insert($data)) {
            return Result::fail(['error' => 'Erreur lors de la création de la commande.', 'messages' => $this->model->errors()], 422);
        }
        $commande = $this->model->find($this->model->getInsertID());
        return Result::created(['data' => $commande]);
    }

    public function update(string $id, array $data): Result
    {
        $commande = $this->model->find($id);
        if (!$commande) return Result::notFound('Commande introuvable.');
        if (isset($data['statut_production'])) {
            $from = (string)($commande['statut_production'] ?? 'En attente matière');
            $to = (string)$data['statut_production'];
            if (!$this->isStatutTransitionValid($from, $to)) {
                return Result::fail([
                    'error' => "Transition de statut interdite : impossible de passer de \"{$from}\" à \"{$to}\". Passage obligatoire par l'étape \"Prête\" avant \"Livrée\".",
                    'code' => 'invalid_status_transition',
                ], 422);
            }
            if ($to === 'Livrée') {
                $data['date_livraison_reelle'] = $data['date_livraison_reelle'] ?? date('Y-m-d');
            }
        }
        if (isset($data['quantite']) || isset($data['prix_unitaire'])) {
            $qte = $data['quantite'] ?? $commande['quantite'];
            $pu = $data['prix_unitaire'] ?? $commande['prix_unitaire'];
            $data['total'] = $qte * $pu;
        }

        // Recalcul quantité tissu si conso ou quantité change
        if (isset($data['conso_tissu_unitaire']) || isset($data['taux_chute_pct']) || isset($data['quantite'])) {
            $conso = (float)($data['conso_tissu_unitaire'] ?? $commande['conso_tissu_unitaire'] ?? 0);
            $tauxChute = (float)($data['taux_chute_pct'] ?? $commande['taux_chute_pct'] ?? 10);
            $quantite = (int)($data['quantite'] ?? $commande['quantite'] ?? 0);
            if ($conso > 0 && $quantite > 0) {
                $tissuParPiece = $conso * (1 + ($tauxChute / 100));
                $data['quantite_tissu_necessaire'] = round($tissuParPiece * $quantite, 3);
            }
        }

        if (isset($data['id'])) unset($data['id']);
        if (!$this->model->update($id, $data)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.', 'messages' => $this->model->errors()], 422);
        }
        return Result::ok(['data' => $this->model->find($id)]);
    }

    public function delete(string $id): Result
    {
        $commande = $this->model->find($id);
        if (!$commande) return Result::notFound('Commande introuvable.');
        $this->model->delete($id);
        return Result::ok(['message' => 'Commande supprimée.']);
    }
}
