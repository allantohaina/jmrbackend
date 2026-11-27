<?php

namespace App\Application\Factures;

use App\Application\Shared\Result;
use App\Libraries\MiniPdf;
use App\Models\PaymentModel;
use App\Models\QuoteModel;

/**
 * Génère à la volée le devis et la facture d'un dossier, sans fichier persisté.
 * Le numéro est dérivé de l'identifiant du devis : stable et unique.
 */
class FactureService
{
    public function generate(string $quoteId, string $kind = 'devis'): Result
    {
        $quote = (new QuoteModel())->getQuoteById($quoteId);
        if (!$quote) return Result::notFound('Devis introuvable.');

        $kind = $kind === 'facture' ? 'facture' : 'devis';
        $numero = ($kind === 'facture' ? 'FAC-' : 'DEV-') . strtoupper(substr($quoteId, 0, 8));
        $total = (float)($quote['amount'] ?? $quote['prix_total_calcule'] ?? 0);

        $pdf = new MiniPdf(148, 210);
        $pdf->setFont(true, 16);
        $pdf->text(18, 22, 'JMR TEXTILE');
        $pdf->setFont(false, 8);
        $pdf->text(18, 29, 'Atelier de confection - Antananarivo, Madagascar');
        $pdf->line(18, 33, 130, 33);

        $pdf->setFont(true, 14);
        $pdf->text(18, 45, $kind === 'facture' ? 'FACTURE' : 'DEVIS');
        $pdf->setFont(false, 9);
        $pdf->text(18, 53, 'Numero : ' . $numero);
        $pdf->text(18, 59, 'Date : ' . date('d/m/Y'));

        $pdf->setFont(true, 10);
        $pdf->text(18, 72, 'Client');
        $pdf->setFont(false, 9);
        $pdf->text(18, 78, (string)($quote['name'] ?? '-'));
        $pdf->text(18, 84, (string)($quote['email'] ?? '-'));
        if (!empty($quote['phone'])) $pdf->text(18, 90, (string)$quote['phone']);

        $pdf->line(18, 98, 130, 98);
        $pdf->setFont(true, 9);
        $pdf->text(18, 105, 'Designation');
        $pdf->text(95, 105, 'Qte');
        $pdf->text(112, 105, 'Montant');
        $pdf->line(18, 108, 130, 108);
        $pdf->setFont(false, 9);
        $designation = trim((string)($quote['titre'] ?? $quote['message'] ?? 'Confection textile'));
        if (function_exists('mb_substr')) $designation = mb_substr($designation, 0, 60);
        else $designation = substr($designation, 0, 60);
        $pdf->text(18, 116, $designation);
        $pdf->text(95, 116, (string)($quote['quantite_commandee'] ?? $quote['quantite'] ?? '-'));
        $pdf->text(112, 116, number_format($total, 2, ',', ' ') . ' Ar');
        $pdf->line(18, 122, 130, 122);
        $pdf->setFont(true, 10);
        $pdf->text(90, 130, 'Total : ' . number_format($total, 2, ',', ' ') . ' Ar');

        if ($kind === 'facture') {
            $payments = (new PaymentModel())->where('quote_id', $quoteId)->orderBy('created_at', 'ASC')->findAll();
            if ($payments) {
                $y = 145;
                $pdf->setFont(true, 9);
                $pdf->text(18, $y, 'Reglements');
                $y += 7;
                foreach ($payments as $p) {
                    $pdf->setFont(false, 8);
                    $pdf->text(18, $y, ucfirst((string)($p['phase'] ?? '-')) . ' - ' . (string)($p['status'] ?? '-'));
                    $pdf->text(100, $y, number_format((float)($p['amount'] ?? 0), 2, ',', ' ') . ' Ar');
                    $y += 6;
                }
            }
        }

        $pdf->setFont(false, 8);
        $pdf->text(18, 190, 'Merci pour votre confiance.');

        return Result::ok([
            'filename' => $numero . '.pdf',
            'pdf' => $pdf->output(),
        ]);
    }
}
