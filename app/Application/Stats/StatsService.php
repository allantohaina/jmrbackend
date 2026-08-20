<?php

namespace App\Application\Stats;

use App\Application\Shared\Result;
use App\Models\AvisProduitModel;
use App\Models\CommandeModel;
use App\Models\MatiereModel;
use App\Models\PaymentModel;
use App\Models\ProduitModel;
use App\Models\QuoteModel;
use App\Models\UserModel;

class StatsService
{
    public function dashboard(): Result
    {
        $quoteModel = new QuoteModel();
        $quotes = $quoteModel->select('status')->findAll();
        $counts = ['pending' => 0, 'sent' => 0, 'accepted' => 0, 'rejected' => 0, 'draft' => 0, 'needs_info' => 0];
        foreach ($quotes as $q) {
            $s = (string)($q['status'] ?? 'pending');
            $counts[$s] = ($counts[$s] ?? 0) + 1;
        }
        $totalDevis = max(1, count($quotes));
        $tauxAcceptation = round(($counts['accepted'] / $totalDevis) * 100, 1);

        $paymentModel = new PaymentModel();
        $payments = $paymentModel->findAll();
        $caTotal = 0;
        $caMois = 0;
        $nbPaiements = 0;
        $moisCourant = date('Y-m');
        foreach ($payments as $p) {
            if (($p['status'] ?? '') !== 'verified') continue;
            $caTotal += (float)($p['amount'] ?? 0);
            $nbPaiements++;
            $date = $p['reviewed_at'] ?? $p['created_at'] ?? '';
            if (substr((string)$date, 0, 7) === $moisCourant) {
                $caMois += (float)($p['amount'] ?? 0);
            }
        }

        $commandeModel = new CommandeModel();
        $commandes = $commandeModel->orderBy('created_at', 'DESC')->findAll();
        $nbCommandes = count($commandes);
        $enCours = 0;
        $livrees = 0;
        $enRetard = 0;
        $today = date('Y-m-d');
        $caLivreMois = 0;
        foreach ($commandes as $c) {
            if (($c['statut_production'] ?? '') === 'Livrée') {
                $livrees++;
                if (($c['date_livraison_reelle'] ?? false) && substr((string)$c['date_livraison_reelle'], 0, 7) === $moisCourant) {
                    $caLivreMois += (float)($c['total'] ?? 0);
                }
            } else {
                $enCours++;
                if (($c['date_livraison_prevue'] ?? false) && $c['date_livraison_prevue'] < $today) {
                    $enRetard++;
                }
            }
        }

        $matieres = (new MatiereModel())->findAll();
        $nbAlertesStock = count(array_filter($matieres, static fn($m) => (float)($m['stock_actuel'] ?? 0) <= (float)($m['stock_seuil'] ?? 0)));

        $avis = (new AvisProduitModel())->where('statut', 'approved')->findAll();
        $noteMoyenne = 0;
        if ($avis !== []) {
            $sum = 0;
            foreach ($avis as $a) $sum += (int)($a['note'] ?? 0);
            $noteMoyenne = round($sum / count($avis), 2);
        }

        $clients = (new UserModel())->where('role', 'user')->countAllResults();
        $employes = (new UserModel())->whereIn('role', ['admin', 'worker'])->countAllResults();
        $nbProduits = (new ProduitModel())->countAllResults();

        return Result::ok([
            'data' => [
                'devis' => [
                    'total' => count($quotes),
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
                    'nb_matieres' => count($matieres),
                    'alertes' => $nbAlertesStock,
                ],
                'satisfaction' => [
                    'note_moyenne' => $noteMoyenne,
                    'nb_avis' => count($avis),
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