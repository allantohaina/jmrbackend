<?php

namespace App\Application\Avis;

use App\Application\Shared\Result;
use App\Models\AvisProduitModel;
use App\Models\ProduitModel;
use App\Models\UserModel;

class AvisService
{
    public function publicList(string $produitId): Result
    {
        if (!(new ProduitModel())->find($produitId)) {
            return Result::notFound('Produit introuvable.');
        }
        $rows = (new AvisProduitModel())
            ->where('produit_id', $produitId)
            ->where('statut', 'approved')
            ->orderBy('created_at', 'DESC')
            ->findAll();
        $noteMoyenne = 0;
        if ($rows !== []) {
            $total = 0;
            foreach ($rows as $r) $total += (int)($r['note'] ?? 0);
            $noteMoyenne = round($total / count($rows), 2);
        }
        foreach ($rows as &$r) {
            $user = !empty($r['user_id']) ? (new UserModel())->find($r['user_id']) : null;
            $r['auteur'] = $user
                ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? 'Client')
                : 'Client';
        }
        return Result::ok([
            'data' => $rows,
            'note_moyenne' => $noteMoyenne,
            'nb_avis' => count($rows),
        ]);
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
        return Result::created(['data' => $model->find($model->getInsertID()), 'message' => 'Merci ! Votre avis sera publié après validation.']);
    }

    public function moderationList(?string $statut = null): Result
    {
        $model = new AvisProduitModel();
        if ($statut && in_array($statut, ['pending', 'approved', 'rejected'], true)) {
            $model->where('statut', $statut);
        }
        $rows = $model->orderBy('created_at', 'DESC')->findAll();
        foreach ($rows as &$r) {
            $produit = (new ProduitModel())->find($r['produit_id'] ?? '');
            $r['produit_nom'] = $produit['nom'] ?? 'Produit supprimé';
            $user = !empty($r['user_id']) ? (new UserModel())->find($r['user_id']) : null;
            $r['auteur'] = $user
                ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? 'Client')
                : 'Anonyme';
        }
        return Result::ok(['data' => $rows]);
    }

    public function updateStatut(string $id, string $statut, ?string $adminId = null): Result
    {
        if (!in_array($statut, ['pending', 'approved', 'rejected'], true)) {
            return Result::fail(['error' => 'Statut invalide.'], 422);
        }
        $model = new AvisProduitModel();
        if (!$model->find($id)) return Result::notFound('Avis introuvable.');
        $model->update($id, ['statut' => $statut]);
        return Result::ok(['data' => $model->find($id)]);
    }
}