<?php

namespace App\Application\Recu;

use App\Application\Shared\Result;
use App\Models\CommandeModel;
use App\Models\PaymentModel;
use App\Models\UserModel;

class RecuService
{
    public function getData(string $commandeId): Result
    {
        $commande = (new CommandeModel())->getCommandeWithClient($commandeId);
        if (!$commande) return Result::notFound('Commande introuvable.');

        $payments = (new PaymentModel())
            ->where('commande_id', $commandeId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $totalPaye = 0;
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'verified') {
                $totalPaye += (float)($p['amount'] ?? 0);
            }
        }

        $clientNom = trim(($commande['client_first_name'] ?? '') . ' ' . ($commande['client_last_name'] ?? ''));
        $restant = max(0, (float)($commande['total'] ?? 0) - $totalPaye);

        return Result::ok([
            'data' => [
                'numero' => $commande['numero'] ?? '',
                'designation' => $commande['designation'] ?? '',
                'quantite' => $commande['quantite'] ?? 0,
                'prix_unitaire' => $commande['prix_unitaire'] ?? 0,
                'total' => $commande['total'] ?? 0,
                'date_commande' => $commande['date_commande'] ?? '',
                'statut_production' => $commande['statut_production'] ?? '',
                'date_livraison_reelle' => $commande['date_livraison_reelle'] ?? null,
                'client_nom' => $clientNom,
                'client_email' => $commande['client_email'] ?? '',
                'client_telephone' => '',
                'total_paye' => round($totalPaye, 2),
                'restant' => round($restant, 2),
                'paiements' => array_map(static fn($p) => [
                    'id' => $p['id'],
                    'phase' => $p['phase'],
                    'amount' => $p['amount'],
                    'status' => $p['status'],
                    'reviewed_at' => $p['reviewed_at'],
                    'transaction_ref' => $p['transaction_ref'] ?? null,
                    'created_at' => $p['created_at'],
                ], $payments),
            ],
        ]);
    }

    public function generatePdf(string $commandeId): Result
    {
        $result = $this->getData($commandeId);
        if (!$result->isSuccess()) return $result;
        $d = $result->getPayload()['data'];

        $client = null;
        $commande = (new CommandeModel())->getCommandeWithClient($commandeId);
        if (!empty($commande['client_id'])) {
            $client = (new UserModel())->find($commande['client_id']);
        }
        if ($client && empty($d['client_telephone'])) {
            $d['client_telephone'] = $client['phone'] ?? '';
        }

        $pdf = new \App\Libraries\MiniPdf(148, 210);
        $y = 14;
        $pdf->setFont(true, 16);
        $pdf->text(74, $y, 'JMR ATELIER');
        $y += 7;
        $pdf->setFont(false, 9);
        $pdf->text(58, $y, 'Confection textile sur-mesure');
        $y += 5;
        $pdf->text(40, $y, $d['client_email'] !== '' ? $d['client_email'] : 'Contact : contact@jmrtextile.com');
        $y += 6;
        $pdf->line(12, $y, 136, $y);
        $y += 10;

        $pdf->setFont(true, 14);
        $pdf->text(52, $y, 'RECU DE PAIEMENT');
        $y += 12;

        $pdf->setFont(false, 10);
        $rows = [
            ['Numéro de commande', $d['numero']],
            ['Désignation', $d['designation']],
            ['Client', $d['client_nom'] ?: '-'],
            ['Date de commande', $d['date_commande']],
            ['Quantité', (string)$d['quantite']],
            ['Prix unitaire', number_format((float)$d['prix_unitaire'], 2, ',', ' ') . ' FCFA'],
            ['Montant total', number_format((float)$d['total'], 2, ',', ' ') . ' FCFA'],
        ];
        foreach ($rows as [$label, $value]) {
            $pdf->setFont(false, 10);
            $pdf->text(14, $y, $label);
            $pdf->setFont(true, 10);
            $pdf->text(62, $y, $value);
            $y += 7;
        }
        $y += 3;
        $pdf->setFont(true, 11);
        $pdf->text(14, $y, 'Total payé : ' . number_format((float)$d['total_paye'], 2, ',', ' ') . ' FCFA');
        $y += 7;
        $pdf->text(14, $y, 'Reste à payer : ' . number_format((float)$d['restant'], 2, ',', ' ') . ' FCFA');
        $y += 10;

        if ($d['paiements'] !== []) {
            $pdf->setFont(true, 11);
            $pdf->text(14, $y, 'Détail des paiements');
            $y += 7;
            $pdf->setFont(false, 9);
            foreach ($d['paiements'] as $p) {
                $label = $p['phase'] === 'deposit' ? 'Acompte' : 'Solde';
                $statut = ($p['status'] ?? '') === 'verified' ? 'REGLE' : strtoupper((string)($p['status'] ?? ''));
                $date = substr((string)($p['reviewed_at'] ?? $p['created_at'] ?? ''), 0, 10);
                $pdf->text(14, $y, $label . '  -  ' . $statut . '  -  ' . $date . '  -  ' . number_format((float)$p['amount'], 2, ',', ' ') . ' FCFA');
                $y += 6;
            }
            $y += 4;
        }

        $pdf->line(12, $y, 136, $y);
        $y += 8;
        $pdf->setFont(false, 8);
        $pdf->text(14, $y, 'Recu genere le ' . date('d/m/Y H:i') . ' - JMR Atelier');
        $y += 5;
        $pdf->text(14, $y, 'Merci pour votre confiance.');

        return Result::ok(['pdf' => $pdf->output(), 'filename' => 'recu-' . $d['numero'] . '.pdf']);
    }
}