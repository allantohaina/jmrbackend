<?php

namespace App\Application\Quotes;

use App\Application\Shared\Result;
use App\Application\Notifications\NotificationService;
use App\Application\DemandesClient\DemandeClientService;
use App\Application\Commandes\CommandeService;
use App\Models\QuoteModel;
use App\Models\UserModel;
use App\Models\ProduitModel;
use CodeIgniter\HTTP\IncomingRequest;

class QuoteService
{
    public function recalculateCotation(array $data): array
    {
        $matiereFourniePar = $data['matiere_fournie_par'] ?? 'atelier';
        $consoTissu = (float)($data['conso_tissu_unitaire'] ?? 0);
        $tauxChute = (float)($data['taux_chute_pct'] ?? 10);
        $niveauDiff = (float)($data['niveau_difficulte'] ?? 1);
        $prixMatiereParMetre = (float)($data['prix_matiere_par_metre'] ?? 0);
        $coutMOPiece = (float)($data['cout_mo_par_piece'] ?? 0);
        $fraisGpct = (float)($data['frais_generaux_pct'] ?? 20);
        $quantite = (int)($data['quantite_commandee'] ?? 0);

        $tissuParPieceAvecChute = $consoTissu * (1 + ($tauxChute / 100));

        if (strtolower((string)$matiereFourniePar) === 'client' || strtolower((string)$matiereFourniePar) === 'cmt') {
            $coutMatiere = 0;
        } else {
            $coutMatiere = $tissuParPieceAvecChute * $prixMatiereParMetre;
        }

        $coutMO = $coutMOPiece * max(1.0, $niveauDiff);
        $coutDirect = $coutMatiere + $coutMO;
        $coutFG = $coutDirect * ($fraisGpct / 100);
        $coutRevientTotal = $coutDirect + $coutFG;

        $marge = 0.25;
        $prixUnitaire = $coutRevientTotal * (1 + $marge);
        $prixTotal = $prixUnitaire * $quantite;

        return [
            'tissu_avec_chute_par_piece' => round($tissuParPieceAvecChute, 4),
            'cout_matiere' => round($coutMatiere, 2),
            'cout_main_oeuvre' => round($coutMO, 2),
            'cout_frais_generaux' => round($coutFG, 2),
            'cout_de_revient' => round($coutRevientTotal, 2),
            'prix_unitaire_calcule' => round($prixUnitaire, 2),
            'prix_total_calcule' => round($prixTotal, 2),
        ];
    }

    private function hydrateWithProduitDefaults(array $data): array
    {
        if (empty($data['produit_id'])) return $data;
        $produit = (new ProduitModel())->findWithDecoded($data['produit_id']);
        if (!$produit) return $data;
        $fields = [
            'conso_tissu_unitaire' => $produit['conso_tissu_unitaire'] ?? null,
            'niveau_difficulte' => $produit['niveau_difficulte_defaut'] ?? null,
            'prix_matiere_par_metre' => $produit['cout_matiere_defaut'] ?? null,
            'cout_mo_par_piece' => $produit['cout_mo_par_piece'] ?? null,
            'frais_generaux_pct' => $produit['frais_generaux_pct'] ?? null,
        ];
        foreach ($fields as $key => $val) {
            if ($val !== null && trim((string)($data[$key] ?? '')) === '') {
                $data[$key] = $val;
            }
        }
        if (empty($data['taux_chute_pct'])) {
            $data['taux_chute_pct'] = 10;
        }
        if (empty($data['matiere_fournie_par'])) {
            $data['matiere_fournie_par'] = 'atelier';
        }
        return $data;
    }

    private const DRAFT_PROGRESSION_FIELDS = ['category', 'tissu', 'quantite', 'delai_souhaite', 'name', 'email', 'message'];

    private const CATEGORY_LABELS = [
        'pantalon' => 'Pantalon', 'jupe' => 'Jupe', 'shirt' => 'T-shirt / Débardeur',
        'polo' => 'Polo', 'chemise' => 'Chemise / Chemisier', 'veste' => 'Veste / Blazer',
        'manteau' => 'Manteau / Parka', 'robe' => 'Robe', 'sweat' => 'Sweat-shirt / Hoodie',
        'short' => 'Short / Bermuda', 'pull' => 'Pull / Cardigan', 'sous-vetement' => 'Sous-vêtements / Lingerie',
        'accessoire' => 'Accessoires', 'uniforme' => 'Uniforme / Workwear', 'sport' => 'Sportswear',
        'enfant' => 'Enfant / Bébé', 'autre' => 'Autre projet sur-mesure',
    ];

    private function computeDraftMeta(array $data): array
    {
        $filled = 0;
        foreach (self::DRAFT_PROGRESSION_FIELDS as $field) {
            if (!empty(trim((string)($data[$field] ?? '')))) {
                $filled++;
            }
        }
        $progression = (int) round(($filled / count(self::DRAFT_PROGRESSION_FIELDS)) * 100);

        $category = trim((string)($data['category'] ?? ''));
        $tissu = trim((string)($data['tissu'] ?? ''));
        $titre = '';
        if ($category !== '') {
            $titre = self::CATEGORY_LABELS[$category] ?? ucfirst($category);
            if ($tissu !== '') {
                $titre .= ' — ' . $tissu;
            }
        } elseif ($tissu !== '') {
            $titre = $tissu;
        } elseif (!empty(trim((string)($data['message'] ?? '')))) {
            $titre = mb_substr(preg_replace('/\s+/', ' ', trim((string)$data['message'])), 0, 60);
        }

        return ['titre' => $titre, 'progression' => $progression];
    }

    public function create(array $data, IncomingRequest $request): Result
    {
        $model = new QuoteModel();
        $files = $request->getFiles();
        $uploadedFiles = [];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/csv', 'application/csv'];
        $attachments = $files['technical_files'] ?? $files['technical_files[]'] ?? [];
        $attachments = is_array($attachments) ? $attachments : [$attachments];
        foreach ($attachments as $file) {
            if (!$file || !($file instanceof \CodeIgniter\HTTP\Files\UploadedFile) || !$file->isValid() || $file->hasMoved()) continue;
            $fileMimeType = $file->getMimeType();
            if ($file->getSize() > 10 * 1024 * 1024 || !in_array($fileMimeType, $allowedMimeTypes, true)) {
                return Result::fail(['message' => 'Format de fichier non autorisé ou fichier trop volumineux.'], 422);
            }
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);
            $uploadedFiles[] = [
                'name' => $file->getName(),
                'url' => base_url('uploads/' . $newName),
                'type' => $fileMimeType,
            ];
        }

        $data = $this->hydrateWithProduitDefaults($data);
        $calc = $this->recalculateCotation($data);

        $isDraft = (($data['status'] ?? '') === 'draft');
        if ($isDraft) {
            $model->skipValidation(true);
        }

        // Lier le devis au compte client s'il existe (client_id absent mais email correspondant)
        $clientId = $data['client_id'] ?? null;
        if (!$clientId && !empty($data['email'])) {
            $linkedUser = (new UserModel())->where('email', $data['email'])->first();
            if ($linkedUser) $clientId = $linkedUser['id'];
        }

        $quoteData = [
            'titre' => null,
            'progression' => 0,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'category' => $data['category'] ?? null,
            'tissu' => $data['tissu'] ?? null,
            'coupe' => $data['coupe'] ?? null,
            'gabarit' => $data['gabarit'] ?? null,
            'style' => $data['style'] ?? null,
            'grammage' => $data['grammage'] ?? null,
            'tailles' => $data['tailles'] ?? null,
            'quantite' => $data['quantite'] ?? null,
            'finitions' => $data['finitions'] ?? null,
            'delai_souhaite' => $data['delai_souhaite'] ?? null,
            'request_type' => $data['request_type'] ?? 'new',
            'modify_code' => $data['modify_code'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'client_id' => $clientId,
            'produit_id' => $data['produit_id'] ?? null,
            'matiere_fournie_par' => $data['matiere_fournie_par'] ?? 'atelier',
            'conso_tissu_unitaire' => $data['conso_tissu_unitaire'] ?? null,
            'taux_chute_pct' => $data['taux_chute_pct'] ?? null,
            'niveau_difficulte' => $data['niveau_difficulte'] ?? null,
            'prix_matiere_par_metre' => $data['prix_matiere_par_metre'] ?? null,
            'cout_mo_par_piece' => $data['cout_mo_par_piece'] ?? null,
            'frais_generaux_pct' => $data['frais_generaux_pct'] ?? null,
            'cout_matiere' => $calc['cout_matiere'],
            'cout_main_oeuvre' => $calc['cout_main_oeuvre'],
            'cout_frais_generaux' => $calc['cout_frais_generaux'],
            'prix_unitaire_calcule' => $calc['prix_unitaire_calcule'],
            'quantite_commandee' => $data['quantite_commandee'] ?? 0,
            'prix_total_calcule' => $calc['prix_total_calcule'],
            'amount' => $data['amount'] ?? $calc['prix_total_calcule'],
            'deposit_amount' => $data['deposit_amount'] ?? null,
            'balance_amount' => $data['balance_amount'] ?? null,
            'deposit_paid' => $data['deposit_paid'] ?? false,
            'balance_paid' => $data['balance_paid'] ?? false,
            'files' => !empty($uploadedFiles) ? json_encode($uploadedFiles) : ($data['files'] ?? null),
        ];

        if ($isDraft) {
            $meta = $this->computeDraftMeta($data);
            $quoteData['titre'] = $meta['titre'];
            $quoteData['progression'] = $meta['progression'];
        }

        if (!$model->save($quoteData)) {
            return Result::fail($model->errors(), 400);
        }

        $quoteId = $model->getInsertID();

        if (!empty($data['demande_id'])) {
            try {
                (new DemandeClientService())->linkToCotation($data['demande_id'], (string)$quoteId);
            } catch (\Throwable) {}
        }

        $quote = $model->getQuoteById($quoteId);

        if (!$isDraft) {
            $admins = (new UserModel())->where('role', 'admin')->findAll();
            $notifications = new NotificationService();
            foreach ($admins as $admin) {
                $notifications->create(
                    $admin['id'], 'quote.requested', 'Nouvelle demande de devis',
                    sprintf('%s a envoyé une demande de devis.', $quote['name'] ?? 'Un client'),
                    'quote', $quoteId, '/backoffice/devis', null, 'info'
                );
            }
        }
        return Result::created($quote);
    }

    public function recalculate(string $id, array $data): Result
    {
        $model = new QuoteModel();
        $quote = $model->getQuoteById($id);
        if (!$quote) return Result::notFound('Devis introuvable');
        $merged = array_merge($quote, $data);
        $merged = $this->hydrateWithProduitDefaults($merged);
        $calc = $this->recalculateCotation($merged);
        $update = [
            'matiere_fournie_par' => $merged['matiere_fournie_par'] ?? $quote['matiere_fournie_par'] ?? null,
            'produit_id' => $merged['produit_id'] ?? $quote['produit_id'] ?? null,
            'conso_tissu_unitaire' => $merged['conso_tissu_unitaire'] ?? $quote['conso_tissu_unitaire'] ?? null,
            'taux_chute_pct' => $merged['taux_chute_pct'] ?? $quote['taux_chute_pct'] ?? null,
            'niveau_difficulte' => $merged['niveau_difficulte'] ?? $quote['niveau_difficulte'] ?? null,
            'prix_matiere_par_metre' => $merged['prix_matiere_par_metre'] ?? $quote['prix_matiere_par_metre'] ?? null,
            'cout_mo_par_piece' => $merged['cout_mo_par_piece'] ?? $quote['cout_mo_par_piece'] ?? null,
            'frais_generaux_pct' => $merged['frais_generaux_pct'] ?? $quote['frais_generaux_pct'] ?? null,
            'quantite_commandee' => $merged['quantite_commandee'] ?? $quote['quantite_commandee'] ?? 0,
            'cout_matiere' => $calc['cout_matiere'],
            'cout_main_oeuvre' => $calc['cout_main_oeuvre'],
            'cout_frais_generaux' => $calc['cout_frais_generaux'],
            'prix_unitaire_calcule' => $calc['prix_unitaire_calcule'],
            'prix_total_calcule' => $calc['prix_total_calcule'],
        ];
        $update['amount'] = $calc['prix_total_calcule'];
        if (!$model->update($id, $update)) {
            return Result::fail($model->errors(), 400);
        }
        return Result::ok(['data' => $model->getQuoteById($id), 'calcul' => $calc]);
    }

    public function convertToCommande(string $quoteId, array $extra = []): Result
    {
        $model = new QuoteModel();
        $quote = $model->getQuoteById($quoteId);
        if (!$quote) return Result::notFound('Devis introuvable');
        if (($quote['status'] ?? '') !== 'accepted') {
            return Result::fail(['error' => 'La cotation doit être acceptée avant de créer une commande.'], 422);
        }
        $userModel = new UserModel();
        $client = !empty($quote['client_id'])
            ? $userModel->find($quote['client_id'])
            : $userModel->where('email', $quote['email'] ?? '')->first();
        $clientId = $client['id'] ?? $extra['client_id'] ?? null;
        if (!$clientId) {
            // Auto-création du compte client à partir de l'email du devis
            $email = strtolower(trim((string)($quote['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return Result::fail(['error' => 'Client introuvable pour créer la commande.'], 422);
            }
            $nameParts = preg_split('/\s+/', trim((string)($quote['name'] ?? '')), 2);
            $insert = $userModel->insert([
                'email' => $email,
                'password' => bin2hex(random_bytes(8)) . 'A1!',
                'first_name' => $nameParts[0] ?: 'Client',
                'last_name' => $nameParts[1] ?? 'JMR',
                'role' => 'user',
            ]);
            if (!$insert) {
                return Result::fail(['error' => 'Impossible de créer le compte client pour la commande.', 'messages' => $userModel->errors()], 422);
            }
            $clientId = $userModel->getInsertID();
        }
        $numero = $extra['numero'] ?? ('CMD-' . strtoupper(substr(md5($quoteId . time()), 0, 8)));
        $quantite = (int)($extra['quantite'] ?? 0);
        if ($quantite <= 0) {
            $quantite = (int)($quote['quantite_commandee'] ?? 0);
        }
        if ($quantite <= 0) {
            $quantite = (int)($quote['quantite'] ?? 0);
        }
        if ($quantite <= 0) {
            $quantite = 1;
        }
        $prixUnitaire = (float)($extra['prix_unitaire'] ?? 0);
        if ($prixUnitaire <= 0) {
            $prixUnitaire = (float)($quote['prix_unitaire_calcule'] ?? 0);
        }
        if ($prixUnitaire <= 0) {
            $prixUnitaire = (float)($quote['amount'] ?? 0);
        }
        if ($quantite > 1 && $prixUnitaire == 0 && ($quote['amount'] ?? 0) > 0) {
            $prixUnitaire = (float)$quote['amount'] / $quantite;
        }
        $commandeData = [
            'cotation_id' => $quoteId,
            'client_id' => $clientId,
            'numero' => $numero,
            'designation' => $extra['designation'] ?? $quote['category'] ?? 'Confection textile',
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'statut_production' => $extra['statut_production'] ?? 'En attente matière',
            'pieces_produites' => 0,
            'date_commande' => $extra['date_commande'] ?? date('Y-m-d'),
            'date_livraison_prevue' => $extra['date_livraison_prevue'] ?? null,
            'notes' => $extra['notes'] ?? null,
        ];
        $service = new CommandeService();
        return $service->create($commandeData);
    }

    public function list(IncomingRequest $request, ?string $userId = null, ?string $role = null): Result
    {
        $model = new QuoteModel();
        $isAdmin = ($role === 'admin');
        if ($userId && !$isAdmin) {
            $userEmail = (new UserModel())->find($userId)['email'] ?? null;
            $quotes = $model->groupStart()
                ->where('client_id', $userId)
                ->orWhere('email', $userEmail)
                ->groupEnd()
                ->orderBy('created_at', 'DESC')
                ->findAll();
            $total = count($quotes);
            return Result::ok(['data' => $quotes, 'total' => $total]);
        }
        $page = max(1, (int) ($request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($request->getGet('per_page') ?? 50)));
        $offset = ($page - 1) * $perPage;
        $quotes = $model->getAllQuotes($perPage, $offset);
        $total = $model->countAll();
        $counts = [
            'draft' => 0, 'needs_info' => 0, 'sent' => 0, 'accepted' => 0, 'rejected' => 0, 'expired' => 0, 'pending' => 0,
        ];
        $all = $model->select('status')->where('status !=', 'draft')->findAll();
        foreach ($all as $q) {
            $s = (string)($q['status'] ?? 'pending');
            if (!isset($counts[$s])) $counts[$s] = 0;
            $counts[$s]++;
        }
        $counts['awaiting_client'] = (int)($counts['sent'] ?? 0);
        return Result::ok([
            'data' => $quotes, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'counts' => $counts,
        ]);
    }

    public function updateStatus(int|string $id, ?string $status, array $additionalData = [], array $actor = []): Result
    {
        $model = new QuoteModel();
        $quote = $model->getQuoteById($id);
        if (!$quote) return Result::notFound('Quote not found');
        $updateData = [];
        if ($status) $updateData['status'] = $status;

        // Set confirmation deadline when sending devis to client
        if ($status === 'sent' && !($quote['confirmation_deadline'] ?? null)) {
            $days = (int)($additionalData['confirmation_days'] ?? $quote['confirmation_days'] ?? 7);
            $updateData['confirmation_days'] = $days;
            $updateData['confirmation_deadline'] = date('Y-m-d H:i:s', time() + ($days * 86400));
        }

        // Un brouillon (status 'draft') est édité par son propriétaire : tous les
        // champs du formulaire sont sauvegardés, et la validation stricte est ignorée.
        $isDraft = ($status === 'draft');
        if ($isDraft) {
            $model->skipValidation(true);
            $meta = $this->computeDraftMeta($additionalData);
            $updateData['titre'] = $meta['titre'];
            $updateData['progression'] = $meta['progression'];
            $draftFields = [
                'name', 'email', 'phone', 'message', 'category', 'tissu', 'coupe',
                'gabarit', 'style', 'grammage', 'tailles', 'quantite', 'finitions',
                'delai_souhaite', 'request_type', 'modify_code',
            ];
            foreach ($draftFields as $key) {
                if (array_key_exists($key, $additionalData)) {
                    $updateData[$key] = $additionalData[$key];
                }
            }
        }

        foreach ($additionalData as $key => $value) {
            $forbidden = ['prix_unitaire_calcule', 'prix_total_calcule', 'cout_matiere', 'cout_main_oeuvre', 'cout_frais_generaux'];
            if (in_array($key, $forbidden, true)) continue;
            if (in_array($key, ['amount', 'deposit_amount', 'balance_amount', 'deposit_paid', 'balance_paid', 'client_id', 'produit_id', 'confirmation_days', 'date_livraison_prevue'])) {
                $updateData[$key] = $value;
            }
        }
        if (!$model->update($id, $updateData)) {
            return Result::fail($model->errors(), 400);
        }

        // Auto-trigger tranche 1 (deposit) when devis is accepted
        if ($status === 'accepted' && ($quote['status'] ?? '') !== 'accepted') {
            $this->createTranche1((string)$id, $model);
        }

        $updatedQuote = $model->getQuoteById($id);
        $client = (new UserModel())->where('email', $updatedQuote['email'] ?? '')->first();
        if ($client && $status) {
            $labels = [
                'sent' => ['Votre devis est prêt', 'Votre devis est disponible. Vous pouvez le consulter et le confirmer.', 'success'],
                'accepted' => ['Devis confirmé', 'Votre devis a été confirmé. Vous pouvez passer à la première tranche de paiement.', 'success'],
                'rejected' => ['Mise à jour du devis', 'Le devis a été mis à jour par notre équipe.', 'warning'],
            ];
            if (isset($labels[$status])) {
                [$title, $message, $type] = $labels[$status];
                (new NotificationService())->create($client['id'], 'quote.' . $status, $title, $message, 'quote', (string) $id, '/mon-profil', $actor['id'] ?? null, $type);
            }
        }
        return Result::ok($updatedQuote);
    }

    public function getQuoteById(string $id): ?array
    {
        $model = new QuoteModel();
        return $model->getQuoteById($id);
    }

    public function confirmByClient(int|string $id, array $actor): Result
    {
        $quote = (new QuoteModel())->getQuoteById($id);
        if (!$quote) return Result::notFound('Devis introuvable');
        if (($actor['role'] ?? null) !== 'user' || strtolower((string) ($actor['email'] ?? '')) !== strtolower((string) ($quote['email'] ?? ''))) {
            return Result::forbidden('Vous ne pouvez pas confirmer ce devis.');
        }
        if (($quote['status'] ?? null) !== 'sent') {
            return Result::fail(['message' => 'Ce devis ne peut pas être confirmé dans son état actuel.'], 422);
        }
        // Check confirmation deadline
        if (!empty($quote['confirmation_deadline']) && strtotime($quote['confirmation_deadline']) < time()) {
            return Result::fail(['message' => 'Le délai de confirmation a expiré. Contactez notre équipe pour recevoir un nouveau devis.'], 410);
        }
        $model = new QuoteModel();
        if (!$model->update($id, ['status' => 'accepted'])) return Result::fail($model->errors(), 400);

        // Auto-trigger tranche 1 (deposit) when client confirms devis
        $this->createTranche1((string)$id, $model);

        $notifications = new NotificationService();
        foreach ((new UserModel())->where('role', 'admin')->findAll() as $admin) {
            $notifications->create(
                $admin['id'], 'quote.accepted', 'Devis confirmé par le client',
                sprintf('%s a confirmé le devis.', $quote['name'] ?? 'Le client'),
                'quote', (string) $id, '/backoffice/devis', $actor['id'] ?? null, 'success'
            );
        }
        return Result::ok($model->getQuoteById($id));
    }

    public function getNotifications(?string $userId): array
    {
        return [];
    }

    public function markAsRead(int|string $id): Result
    {
        return Result::ok(['message' => 'Notification marked read']);
    }

    private function createTranche1(string $quoteId, QuoteModel $model): void
    {
        $quote = $model->getQuoteById($quoteId);
        if (!$quote) return;

        $amount = (float)($quote['amount'] ?? 0);
        if ($amount <= 0) return;

        $depositAmount = round($amount * 0.5, 2);

        $paymentModel = new \App\Models\PaymentModel();
        $existing = $paymentModel->where('quote_id', $quoteId)
            ->where('phase', 'deposit')
            ->first();
        if ($existing) return;

        $paymentData = [
            'quote_id' => $quoteId,
            'phase' => 'deposit',
            'amount' => $depositAmount,
            'status' => 'submitted',
        ];

        if (!$paymentModel->insert($paymentData)) {
            log_message('error', 'Failed to auto-create tranche 1 for quote ' . $quoteId . ': ' . json_encode($paymentModel->errors()));
            return;
        }

        $model->update($quoteId, [
            'deposit_amount' => $depositAmount,
            'deposit_paid' => false,
        ]);
    }
}
