<?php

namespace App\Application\Avis;

use App\Application\Shared\Result;
use App\Models\AvisProduitModel;
use App\Models\ProduitModel;

class AvisService
{
    public function publicList(string $produitId): Result
    {
        $cacheKey = 'avis_public_' . $produitId;
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return Result::ok($cached);
        }

        if (!(new ProduitModel())->find($produitId)) {
            return Result::notFound('Produit introuvable.');
        }

        $rows = $this->listWithAuteurs('approved', $produitId, 100);
        $noteMoyenne = 0;
        if ($rows !== []) {
            $total = 0;
            foreach ($rows as $r) $total += (int)($r['note'] ?? 0);
            $noteMoyenne = round($total / count($rows), 2);
        }

        $payload = [
            'data' => $rows,
            'note_moyenne' => $noteMoyenne,
            'nb_avis' => count($rows),
        ];

        cache($cacheKey, $payload, 120);

        return Result::ok($payload);
    }

    private function listWithAuteurs(string $statut, ?string $produitId = null, ?int $limit = null): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('avis_produits ap')
            ->select('ap.*, u.first_name, u.last_name, u.email, p.nom AS produit_nom')
            ->join('users u', 'u.id = ap.user_id', 'left')
            ->join('produits p', 'p.id = ap.produit_id', 'left')
            ->where('ap.statut', $statut);
        if ($produitId !== null) {
            $builder->where('ap.produit_id', $produitId);
        }
        if ($limit !== null) {
            $builder->limit($limit);
        }
        $rows = $builder->orderBy('ap.created_at', 'DESC')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['auteur'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: ($r['email'] ?? ($r['user_id'] ? 'Client' : 'Anonyme'));
            $r['produit_nom'] = $r['produit_nom'] ?? 'Produit supprimé';
            unset($r['first_name'], $r['last_name'], $r['email']);
        }
        return $rows;
    }

    public function submit(string $produitId, array $data, ?string $userId = null): Result
    {
        if (!(new ProduitModel())->find($produitId)) {
            return Result::notFound('Produit introuvable.');
        }
        $model = new AvisProduitModel();
        $avis = [
            'produit_id' => $produitId,
            'user_id' => $userId,
            'note' => (int)($data['note'] ?? 0),
            'commentaire' => $data['commentaire'] ?? null,
            'statut' => 'pending',
        ];
        if (!$model->insert($avis)) {
            return Result::fail(['error' => 'Erreur lors de l\'envoi de l\'avis.', 'messages' => $model->errors()], 422);
        }
        cache()->delete('avis_public_' . $produitId);
        return Result::created(['data' => $model->find($model->getInsertID()), 'message' => 'Merci ! Votre avis sera publié après validation.']);
    }

    public function moderationList(?string $statut = null): Result
    {
        $model = new AvisProduitModel();
        if ($statut && in_array($statut, ['pending', 'approved', 'rejected'], true)) {
            $rows = $this->listWithAuteurs($statut);
        } else {
            $rows = $this->listWithAuteurs('pending');
            $rows = array_merge($rows, $this->listWithAuteurs('approved'));
            $rows = array_merge($rows, $this->listWithAuteurs('rejected'));
        }
        return Result::ok(['data' => $rows]);
    }

    public function updateStatut(string $id, string $statut, ?string $adminId = null): Result
    {
        if (!in_array($statut, ['pending', 'approved', 'rejected'], true)) {
            return Result::fail(['error' => 'Statut invalide.'], 422);
        }
        $model = new AvisProduitModel();
        $existing = $model->find($id);
        if (!$existing) return Result::notFound('Avis introuvable.');
        $model->update($id, ['statut' => $statut]);
        if (!empty($existing['produit_id'])) {
            cache()->delete('avis_public_' . $existing['produit_id']);
        }
        return Result::ok(['data' => $model->find($id)]);
    }
}