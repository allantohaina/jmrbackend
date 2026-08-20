<?php

namespace App\Application\Stats;

use App\Application\Shared\Result;
use App\Models\ProduitModel;
use App\Models\UserModel;

class StatsService
{
    public function dashboard(): Result
    {
        $db = \Config\Database::connect();

        // --- Devis (agrégat par statut) ---
        $counts = ['pending' => 0, 'sent' => 0, 'accepted' => 0, 'rejected' => 0, 'draft' => 0, 'needs_info' => 0];
        $totalDevis = 0;
        $quoteRows = $db->table('quotes')
            ->select('status, COUNT(*) AS c')
            ->where('deleted_at IS NULL')
            ->groupBy('status')
            ->get()->getResultArray();
        foreach ($quoteRows as $q) {
            $s = (string) ($q['status'] ?? 'pending');
            $counts[$s] = (int) ($q['c'] ?? 0);
            $totalDevis += (int) ($q['c'] ?? 0);
        }
        $tauxAcceptation = $totalDevis > 0 ? round(($counts['accepted'] / $totalDevis) * 100, 1) : 0;

        // --- Paiements (agrégats SQL) ---
        $caTotal = (float) ($db->table('payments')
            ->select('COALESCE(SUM(amount), 0) AS v')
            ->where('status', 'verified')
            ->get()->getRowArray()['v'] ?? 0);
        $nbPaiements = $db->table('payments')->where('status', 'verified')->countAllResults();
        $caMois = (float) ($db->table('payments')
            ->select('COALESCE(SUM(amount), 0) AS v')
            ->where('status', 'verified')
            ->where('COALESCE(reviewed_at, created_at) >=', date('Y-m-01 00:00:00'))
            ->get()->getRowArray()['v'] ?? 0);

        // --- Commandes (agrégats SQL) ---
        $today = date('Y-m-d');
        $nbCommandes = $db->table('commandes')->where('deleted_at IS NULL')->countAllResults();
        $livrees = $db->table('commandes')
            ->where('deleted_at IS NULL')
            ->where('statut_production', 'Livrée')
            ->countAllResults();
        $enCours = $db->table('commandes')
            ->where('deleted_at IS NULL')
            ->where('statut_production !=', 'Livrée')
            ->countAllResults();
        $enRetard = $db->table('commandes')
            ->where('deleted_at IS NULL')
            ->where('statut_production !=', 'Livrée')
            ->where('date_livraison_prevue IS NOT NULL')
            ->where('date_livraison_prevue <', $today)
            ->countAllResults();
        $caLivreMois = (float) ($db->table('commandes')
            ->select('COALESCE(SUM(total), 0) AS v')
            ->where('deleted_at IS NULL')
            ->where('statut_production', 'Livrée')
            ->where('date_livraison_reelle >=', date('Y-m-01 00:00:00'))
            ->get()->getRowArray()['v'] ?? 0);

        // --- Matières premières (agrégats SQL) ---
        $nbMatieres = $db->table('matieres')->where('deleted_at IS NULL')->countAllResults();
        $nbAlertesStock = $db->table('matieres')
            ->where('deleted_at IS NULL')
            ->where('stock_actuel <= stock_seuil')
            ->countAllResults();

        // --- Avis (agrégat SQL) ---
        $avisAgg = $db->table('avis_produits')
            ->select('COUNT(*) AS c, COALESCE(AVG(note), 0) AS avg')
            ->where('statut', 'approved')
            ->get()->getRowArray();
        $nbAvis = (int) ($avisAgg['c'] ?? 0);
        $noteMoyenne = round((float) ($avisAgg['avg'] ?? 0), 2);

        $clients = (new UserModel())->where('role', 'user')->countAllResults();
        $employes = (new UserModel())->whereIn('role', ['admin', 'worker'])->countAllResults();
        $nbProduits = (new ProduitModel())->countAllResults();

        return Result::ok([
            'data' => [
                'devis' => [
                    'total' => $totalDevis,
                    'en_attente' => ($counts['pending'] ?? 0) + ($counts['needs_info'] ?? 0),
                    'envoyes' => $counts['sent'] ?? 0,
                    'acceptes' => $counts['accepted'] ?? 0,
                    'refuses' => $counts['rejected'] ?? 0,
                    'taux_acceptation' => $tauxAcceptation,
                ],
                'finance' => [
                    'ca_total' => round($caTotal, 2),
                    'ca_mois' => round($caMois, 2),
                    'ca_livre_mois' => round($caLivreMois, 2),
                    'nb_paiements' => $nbPaiements,
                ],
                'commandes' => [
                    'total' => $nbCommandes,
                    'en_cours' => $enCours,
                    'livrees' => $livrees,
                    'en_retard' => $enRetard,
                ],
                'stock' => [
                    'nb_matieres' => $nbMatieres,
                    'alertes' => $nbAlertesStock,
                ],
                'satisfaction' => [
                    'note_moyenne' => $noteMoyenne,
                    'nb_avis' => $nbAvis,
                ],
                'relationnel' => [
                    'clients' => $clients,
                    'employes' => $employes,
                    'produits' => $nbProduits,
                ],
            ],
        ]);
    }
}