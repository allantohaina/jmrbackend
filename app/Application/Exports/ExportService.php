<?php

namespace App\Application\Exports;

use App\Application\Shared\Result;
use App\Models\CommandeModel;
use App\Models\PaymentModel;
use App\Models\QuoteModel;

class ExportService
{
    public function devisCsv(): Result
    {
        $rows = (new QuoteModel())->orderBy('created_at', 'DESC')->findAll();
        $headers = ['ID', 'Titre', 'Nom', 'Email', 'Telephone', 'Categorie', 'Statut', 'Montant', 'Date'];
        $lines = [$this->line($headers)];
        foreach ($rows as $r) {
            $lines[] = $this->line([
                (string)($r['id'] ?? ''),
                (string)($r['titre'] ?? ''),
                (string)($r['name'] ?? ''),
                (string)($r['email'] ?? ''),
                (string)($r['phone'] ?? ''),
                (string)($r['category'] ?? ''),
                (string)($r['status'] ?? ''),
                (string)($r['amount'] ?? ''),
                substr((string)($r['created_at'] ?? ''), 0, 10),
            ]);
        }
        return Result::ok(['csv' => implode('', $lines), 'filename' => 'devis-' . date('Y-m-d') . '.csv']);
    }

    public function commandesCsv(): Result
    {
        $rows = (new CommandeModel())->getCommandesWithClient();
        $headers = ['Numero', 'Client', 'Designation', 'Quantite', 'Prix unitaire', 'Total', 'Statut production', 'Date commande', 'Livraison prevue', 'En retard'];
        $lines = [$this->line($headers)];
        foreach ($rows as $r) {
            $client = trim(($r['client_first_name'] ?? '') . ' ' . ($r['client_last_name'] ?? ''));
            $lines[] = $this->line([
                (string)($r['numero'] ?? ''),
                $client ?: (string)($r['client_email'] ?? ''),
                (string)($r['designation'] ?? ''),
                (string)($r['quantite'] ?? ''),
                (string)($r['prix_unitaire'] ?? ''),
                (string)($r['total'] ?? ''),
                (string)($r['statut_production'] ?? ''),
                (string)($r['date_commande'] ?? ''),
                (string)($r['date_livraison_prevue'] ?? ''),
                !empty($r['en_retard']) ? 'Oui' : 'Non',
            ]);
        }
        return Result::ok(['csv' => implode('', $lines), 'filename' => 'commandes-' . date('Y-m-d') . '.csv']);
    }

    public function paiementsCsv(): Result
    {
        $rows = (new PaymentModel())->orderBy('created_at', 'DESC')->findAll();
        $headers = ['ID', 'Devis', 'Commande', 'Phase', 'Montant', 'Statut', 'Type', 'Reference', 'Date'];
        $lines = [$this->line($headers)];
        foreach ($rows as $r) {
            $lines[] = $this->line([
                (string)($r['id'] ?? ''),
                (string)($r['quote_id'] ?? ''),
                (string)($r['commande_id'] ?? ''),
                (string)($r['phase'] ?? ''),
                (string)($r['amount'] ?? ''),
                (string)($r['status'] ?? ''),
                (string)($r['payment_type'] ?? ''),
                (string)($r['transaction_ref'] ?? ''),
                substr((string)($r['created_at'] ?? ''), 0, 10),
            ]);
        }
        return Result::ok(['csv' => implode('', $lines), 'filename' => 'paiements-' . date('Y-m-d') . '.csv']);
    }

    private function line(array $fields): string
    {
        $escaped = array_map(static fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $fields);
        return implode(';', $escaped) . "\r\n";
    }
}