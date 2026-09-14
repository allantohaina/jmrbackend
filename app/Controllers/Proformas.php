<?php

namespace App\Controllers;

use App\Models\ProformaModel;
use App\Models\QuoteModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Proformas extends ResourceController
{
    protected $format = 'json';

    private const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'];
    private const CURRENCIES = ['MGA', 'EUR', 'USD'];

    public function index(): ResponseInterface
    {
        try {
            $model = new ProformaModel();
            $status = $this->request->getGet('status');
            $search = trim((string) $this->request->getGet('search'));
            $builder = $model->orderBy('year', 'DESC')->orderBy('seq', 'DESC');
            if (in_array($status, self::STATUSES, true)) {
                $builder->where('status', $status);
            }
            if ($search !== '') {
                $builder->groupStart()
                    ->like('number', $search)
                    ->orLike('client_name', $search)
                    ->orLike('client_email', $search)
                    ->groupEnd();
            }
            $rows = $builder->findAll();
            return $this->respond(['status' => 'success', 'data' => array_map([$this, 'present'], $rows)]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas index error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $row = (new ProformaModel())->find($id);
            if (!$row) {
                return $this->failNotFound('Proforma introuvable.');
            }
            return $this->respond(['status' => 'success', 'data' => $this->present($row)]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas show error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function nextNumber(): ResponseInterface
    {
        try {
            $year = (int) date('Y');
            $seq = (new ProformaModel())->nextSequence($year);
            return $this->respond(['status' => 'success', 'data' => [
                'number' => sprintf('PRO-%d-%04d', $year, $seq),
                'seq' => $seq,
                'year' => $year,
            ]]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas nextNumber error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $actor = $this->request->user ?? [];

            $errors = $this->validatePayload($input, true);
            if ($errors !== []) {
                return $this->failValidationErrors($errors);
            }

            $model = new ProformaModel();
            $year = (int) date('Y');
            $seq = $model->nextSequence($year);
            $totals = $this->computeTotals($input);

            $data = $this->sanitize($input);
            $data['year'] = $year;
            $data['seq'] = $seq;
            $data['number'] = sprintf('PRO-%d-%04d', $year, $seq);
            $data['status'] = in_array($input['status'] ?? 'draft', self::STATUSES, true) ? $input['status'] : 'draft';
            $data = array_merge($data, $totals);
            $data['created_by'] = $actor['id'] ?? null;
            if (empty($data['issue_date'])) {
                $data['issue_date'] = date('Y-m-d');
            }
            if (empty($data['valid_until'])) {
                $data['valid_until'] = date('Y-m-d', time() + 30 * 86400);
            }

            $id = $model->insert($data, true);
            if (!$id) {
                return $this->failValidationErrors($model->errors() ?: ['lines' => 'Données invalides.']);
            }
            $fresh = $model->find($model->getInsertID() ?: $id);
            return $this->respondCreated(['status' => 'success', 'data' => $this->present($fresh ?: $data)]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas create error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function update($id = null): ResponseInterface
    {
        try {
            $model = new ProformaModel();
            $existing = $model->find($id);
            if (!$existing) {
                return $this->failNotFound('Proforma introuvable.');
            }
            if (in_array($existing['status'] ?? '', ['accepted', 'converted'], true) && ($this->request->user['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Proforma verrouillée.');
            }

            $input = $this->getInputData();
            $errors = $this->validatePayload($input, false);
            if ($errors !== []) {
                return $this->failValidationErrors($errors);
            }

            $merged = array_merge($existing, $this->sanitize($input));
            if (isset($input['status']) && in_array($input['status'], self::STATUSES, true)) {
                $merged['status'] = $input['status'];
            }
            $totals = $this->computeTotals($merged);
            $merged = array_merge($merged, $totals);
            unset($merged['number'], $merged['seq'], $merged['year']);

            if (!$model->update($id, $merged)) {
                return $this->failValidationErrors($model->errors() ?: ['lines' => 'Mise à jour impossible.']);
            }
            return $this->respond(['status' => 'success', 'data' => $this->present($model->find($id))]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas update error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function remove($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut supprimer une proforma.');
            }
            $model = new ProformaModel();
            if (!$model->find($id)) {
                return $this->failNotFound('Proforma introuvable.');
            }
            $model->delete($id);
            return $this->respondDeleted(['message' => 'Proforma supprimée.']);
        } catch (Throwable $e) {
            log_message('error', 'Proformas remove error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function fromQuote($quoteId = null): ResponseInterface
    {
        try {
            $quote = (new QuoteModel())->getQuoteById((string) $quoteId);
            if (!$quote) {
                return $this->failNotFound('Devis introuvable.');
            }
            $amount = (float) ($quote['amount'] ?? 0);
            if ($amount <= 0) {
                return $this->failValidationErrors(['amount' => 'Devis non chiffré — chiffrez-le avant de générer une proforma.']);
            }

            $model = new ProformaModel();
            $existing = $model->where('quote_id', (string) $quoteId)->where('status !=', 'rejected')->first();
            if ($existing) {
                return $this->respond(['status' => 'success', 'data' => $this->present($existing), 'reused' => true]);
            }

            $qty = max(1, (int) ($quote['quantite_commandee'] ?? $quote['quantite'] ?? 1));
            $taxRate = 20.0;
            $unitHT = $amount / (1 + $taxRate / 100) / $qty;
            $lines = [[
                'description' => $quote['category'] ? 'Confection textile — ' . $quote['category'] : 'Prestation de confection textile',
                'detail' => mb_substr((string) ($quote['message'] ?? ''), 0, 160),
                'quantity' => $qty,
                'unit' => 'pce',
                'unitPrice' => round($unitHT, 2),
                'taxRate' => $taxRate,
            ]];

            $year = (int) date('Y');
            $seq = $model->nextSequence($year);
            $payload = [
                'client_name' => $quote['name'] ?? 'Client',
                'client_email' => $quote['email'] ?? null,
                'client_phone' => $quote['phone'] ?? null,
                'client_address' => null,
                'currency' => 'MGA',
                'tax_rate' => $taxRate,
                'discount_pct' => 0,
                'deposit_pct' => 50,
                'lines' => $lines,
                'order_reference' => 'DEV-' . strtoupper(substr((string) $quote['id'], 0, 8)),
                'payment_terms' => 'Acompte de 50% à la commande, solde à la livraison.',
                'notes' => $quote['message'] ?? null,
            ];
            $totals = $this->computeTotals($payload);
            $data = $this->sanitize($payload);
            $data['quote_id'] = (string) $quoteId;
            $data['client_id'] = $quote['client_id'] ?? null;
            $data['year'] = $year;
            $data['seq'] = $seq;
            $data['number'] = sprintf('PRO-%d-%04d', $year, $seq);
            $data['status'] = 'draft';
            $data = array_merge($data, $totals);
            $data['created_by'] = $this->request->user['id'] ?? null;
            $data['issue_date'] = date('Y-m-d');
            $data['valid_until'] = date('Y-m-d', time() + 30 * 86400);

            $model->insert($data);
            $fresh = $model->where('number', $data['number'])->first();
            return $this->respondCreated(['status' => 'success', 'data' => $this->present($fresh ?: $data)]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas fromQuote error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function sign($id = null): ResponseInterface
    {
        try {
            $actor = $this->request->user ?? [];
            if (($actor['role'] ?? null) !== 'admin') {
                return $this->failForbidden('Seul un administrateur peut signer un document.');
            }
            $input = $this->getInputData();
            $name = trim((string) ($input['admin_signature_name'] ?? ''));
            if ($name === '') {
                return $this->failValidationErrors(['admin_signature_name' => 'Le nom du signataire est requis.']);
            }
            $model = new ProformaModel();
            if (!$model->find($id)) {
                return $this->failNotFound('Proforma introuvable.');
            }
            $model->update($id, [
                'admin_signature_name' => $name,
                'admin_signature_at' => is_string($input['admin_signature_at'] ?? null) ? $input['admin_signature_at'] : date('Y-m-d H:i:s'),
            ]);
            return $this->respond(['status' => 'success', 'data' => $this->present($model->find($id))]);
        } catch (Throwable $e) {
            log_message('error', 'Proformas sign error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    // ---------- helpers ----------

    /** @return array<string,string> */
    private function validatePayload(array $input, bool $isCreate): array
    {
        $errors = [];
        if ($isCreate && trim((string) ($input['client_name'] ?? '')) === '') {
            $errors['client_name'] = 'Le nom du client est requis.';
        }
        if (isset($input['client_email']) && $input['client_email'] !== '' && !filter_var($input['client_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['client_email'] = 'Email client invalide.';
        }
        if (isset($input['currency']) && !in_array($input['currency'], self::CURRENCIES, true)) {
            $errors['currency'] = 'Devise invalide (MGA, EUR, USD).';
        }
        if (isset($input['status']) && !in_array($input['status'], self::STATUSES, true)) {
            $errors['status'] = 'Statut invalide.';
        }
        if (array_key_exists('lines', $input)) {
            $lines = is_string($input['lines']) ? json_decode($input['lines'], true) : $input['lines'];
            if (!is_array($lines) || $lines === []) {
                $errors['lines'] = 'Ajoutez au moins une ligne.';
            } else {
                foreach (array_slice($lines, 0, 60) as $i => $line) {
                    if (!is_array($line) || trim((string) ($line['description'] ?? '')) === '') {
                        $errors["lines.$i.description"] = 'Description requise.';
                    }
                    if ((float) ($line['quantity'] ?? 0) <= 0) {
                        $errors["lines.$i.quantity"] = 'Quantité invalide.';
                    }
                    if ((float) ($line['unitPrice'] ?? -1) < 0) {
                        $errors["lines.$i.unitPrice"] = 'Prix invalide.';
                    }
                }
            }
        } elseif ($isCreate) {
            $errors['lines'] = 'Ajoutez au moins une ligne.';
        }
        foreach (['tax_rate', 'discount_pct', 'deposit_pct'] as $field) {
            if (isset($input[$field]) && ((float) $input[$field] < 0 || (float) $input[$field] > 100)) {
                $errors[$field] = 'Doit être entre 0 et 100.';
            }
        }
        return $errors;
    }

    private function sanitize(array $input): array
    {
        $lines = $input['lines'] ?? [];
        if (is_string($lines)) {
            $decoded = json_decode($lines, true);
            $lines = is_array($decoded) ? $decoded : [];
        }
        $cleanLines = [];
        foreach (array_slice(is_array($lines) ? $lines : [], 0, 60) as $line) {
            if (!is_array($line)) {
                continue;
            }
            $cleanLines[] = [
                'description' => mb_substr(trim((string) ($line['description'] ?? '')), 0, 255),
                'detail' => mb_substr(trim((string) ($line['detail'] ?? $line['reference'] ?? '')), 0, 300),
                'quantity' => max(0, (float) ($line['quantity'] ?? 1)),
                'unit' => mb_substr((string) ($line['unit'] ?? 'pce'), 0, 20),
                'unitPrice' => max(0, (float) ($line['unitPrice'] ?? 0)),
                'taxRate' => isset($line['taxRate']) ? max(0, min(100, (float) $line['taxRate'])) : null,
            ];
        }
        return [
            'quote_id' => isset($input['quote_id']) && $input['quote_id'] !== '' ? (string) $input['quote_id'] : null,
            'client_id' => isset($input['client_id']) && $input['client_id'] !== '' ? (string) $input['client_id'] : null,
            'client_name' => mb_substr(trim((string) ($input['client_name'] ?? '')), 0, 255),
            'client_email' => isset($input['client_email']) && $input['client_email'] !== '' ? trim((string) $input['client_email']) : null,
            'client_phone' => isset($input['client_phone']) ? mb_substr(trim((string) $input['client_phone']), 0, 50) : null,
            'client_address' => isset($input['client_address']) ? mb_substr(trim((string) $input['client_address']), 0, 1000) : null,
            'client_tax_id' => isset($input['client_tax_id']) ? mb_substr(trim((string) $input['client_tax_id']), 0, 100) : null,
            'currency' => in_array($input['currency'] ?? 'MGA', self::CURRENCIES, true) ? $input['currency'] : 'MGA',
            'tax_rate' => isset($input['tax_rate']) ? (float) $input['tax_rate'] : 20.0,
            'discount_pct' => isset($input['discount_pct']) ? (float) $input['discount_pct'] : 0.0,
            'deposit_pct' => isset($input['deposit_pct']) ? (float) $input['deposit_pct'] : 50.0,
            'lines' => json_encode($cleanLines, JSON_UNESCAPED_UNICODE),
            'issue_date' => $input['issue_date'] ?? null,
            'valid_until' => $input['valid_until'] ?? null,
            'delivery_address' => isset($input['delivery_address']) ? mb_substr(trim((string) $input['delivery_address']), 0, 1000) : null,
            'order_reference' => isset($input['order_reference']) ? mb_substr(trim((string) $input['order_reference']), 0, 100) : null,
            'payment_terms' => isset($input['payment_terms']) ? mb_substr(trim((string) $input['payment_terms']), 0, 2000) : null,
            'notes' => isset($input['notes']) ? mb_substr(trim((string) $input['notes']), 0, 2000) : null,
        ];
    }

    /** @return array<string,float> */
    private function computeTotals(array $input): array
    {
        $lines = $input['lines'] ?? [];
        if (is_string($lines)) {
            $decoded = json_decode($lines, true);
            $lines = is_array($decoded) ? $decoded : [];
        }
        $defaultTax = (float) ($input['tax_rate'] ?? 20);
        $subtotal = 0.0;
        $tax = 0.0;
        foreach (is_array($lines) ? $lines : [] as $line) {
            if (!is_array($line)) {
                continue;
            }
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unitPrice'] ?? 0);
            $rate = isset($line['taxRate']) && $line['taxRate'] !== null && $line['taxRate'] !== '' ? (float) $line['taxRate'] : $defaultTax;
            $lineTotal = $qty * $price;
            $subtotal += $lineTotal;
            $tax += $lineTotal * ($rate / 100);
        }
        $discountPct = (float) ($input['discount_pct'] ?? 0);
        $discount = $subtotal * ($discountPct / 100);
        $taxable = max(0, $subtotal - $discount);
        // Remise appliquée au prorata sur la taxe
        $taxAfter = $subtotal > 0 ? $tax * ($taxable / $subtotal) : 0;
        $total = $taxable + $taxAfter;
        $depositPct = (float) ($input['deposit_pct'] ?? 50);
        $deposit = $total * ($depositPct / 100);
        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'tax_amount' => round($taxAfter, 2),
            'total' => round($total, 2),
            'deposit_amount' => round($deposit, 2),
            'balance_amount' => round($total - $deposit, 2),
        ];
    }

    /** @param array<string,mixed> $row */
    private function present(array $row): array
    {
        $model = new ProformaModel();
        $row['lines'] = $model->decodeLines($row['lines'] ?? null);
        foreach (['subtotal', 'discount_amount', 'tax_amount', 'total', 'deposit_amount', 'balance_amount', 'tax_rate', 'discount_pct', 'deposit_pct'] as $k) {
            if (isset($row[$k])) {
                $row[$k] = (float) $row[$k];
            }
        }
        return $row;
    }

    private function getInputData(): array
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'multipart/form-data')) {
            $post = $this->request->getPost();
            return is_array($post) ? $post : [];
        }
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (Throwable) {
        }
        $raw = $this->request->getRawInput();
        if (is_array($raw) && $raw !== []) {
            return $raw;
        }
        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}
