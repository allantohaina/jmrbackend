<?php

namespace App\Application\MatierePremiere;

use App\Application\Shared\Result;
use App\Models\MatiereModel;
use App\Models\MouvementStockModel;

class MatierePremiereService
{
    public function list(): Result
    {
        $model = new MatiereModel();
        $matieres = $model->orderBy('nom', 'ASC')->findAll();
        $alertes = [];
        foreach ($matieres as &$m) {
            $m['alerte'] = (float)($m['stock_actuel'] ?? 0) <= (float)($m['stock_seuil'] ?? 0);
            if ($m['alerte']) {
                $alertes[] = $m;
            }
        }
        return Result::ok(['data' => $matieres, 'alertes' => $alertes, 'nb_alertes' => count($alertes)]);
    }

    public function getById(string $id): Result
    {
        $matiere = (new MatiereModel())->find($id);
        if (!$matiere) return Result::notFound('Matière introuvable.');
        $matiere['alerte'] = (float)($matiere['stock_actuel'] ?? 0) <= (float)($matiere['stock_seuil'] ?? 0);
        $mouvements = (new MouvementStockModel())
            ->where('matiere_id', $id)
            ->orderBy('created_at', 'DESC')
            ->findAll();
        return Result::ok(['data' => $matiere, 'mouvements' => $mouvements]);
    }

    public function create(array $data): Result
    {
        $model = new MatiereModel();
        $clean = [
            'nom' => trim((string)($data['nom'] ?? '')),
            'unite' => trim((string)($data['unite'] ?? 'm')),
            'stock_actuel' => (float)($data['stock_actuel'] ?? 0),
            'stock_seuil' => (float)($data['stock_seuil'] ?? 0),
            'prix_unite' => (float)($data['prix_unite'] ?? 0),
            'fournisseur' => $data['fournisseur'] ?? null,
            'description' => $data['description'] ?? null,
        ];
        if (!$model->insert($clean)) {
            return Result::fail(['error' => 'Erreur lors de la création de la matière.', 'messages' => $model->errors()], 422);
        }
        return Result::created(['data' => $model->find($model->getInsertID())]);
    }

    public function update(string $id, array $data): Result
    {
        $model = new MatiereModel();
        $matiere = $model->find($id);
        if (!$matiere) return Result::notFound('Matière introuvable.');
        $clean = [];
        foreach (['nom', 'unite', 'fournisseur', 'description'] as $key) {
            if (array_key_exists($key, $data)) {
                $clean[$key] = ($key === 'description' || $key === 'fournisseur') ? $data[$key] : trim((string)$data[$key]);
            }
        }
        foreach (['stock_actuel', 'stock_seuil', 'prix_unite'] as $key) {
            if (array_key_exists($key, $data)) {
                $clean[$key] = (float)$data[$key];
            }
        }
        if ($clean === []) return Result::ok(['data' => $matiere]);
        if (!$model->update($id, $clean)) {
            return Result::fail(['error' => 'Erreur lors de la mise à jour.', 'messages' => $model->errors()], 422);
        }
        return Result::ok(['data' => $model->find($id)]);
    }

    public function delete(string $id): Result
    {
        $model = new MatiereModel();
        if (!$model->find($id)) return Result::notFound('Matière introuvable.');
        $model->delete($id);
        return Result::ok(['message' => 'Matière supprimée.']);
    }

    public function mouvement(array $data, ?string $actorId = null): Result
    {
        $matiere = (new MatiereModel())->find($data['matiere_id'] ?? '');
        if (!$matiere) return Result::notFound('Matière introuvable.');
        $type = (string)($data['type'] ?? '');
        $quantite = (float)($data['quantite'] ?? 0);
        if (!in_array($type, ['entree', 'sortie', 'ajustement'], true) || $quantite <= 0) {
            return Result::fail(['error' => 'Type ou quantité de mouvement invalide.'], 422);
        }

        $mouvementModel = new MouvementStockModel();
        $mouvement = [
            'matiere_id' => $matiere['id'],
            'type' => $type,
            'quantite' => $quantite,
            'motif' => $data['motif'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
        ];
        if (!$mouvementModel->insert($mouvement)) {
            return Result::fail(['error' => 'Erreur lors de l\'enregistrement du mouvement.', 'messages' => $mouvementModel->errors()], 422);
        }

        $nouveauStock = (float)$matiere['stock_actuel'];
        if ($type === 'entree') {
            $nouveauStock += $quantite;
        } elseif ($type === 'sortie') {
            $nouveauStock -= $quantite;
        } else {
            $nouveauStock = $quantite;
        }
        if ($nouveauStock < 0) {
            $nouveauStock = 0;
        }

        (new MatiereModel())->update($matiere['id'], ['stock_actuel' => $nouveauStock]);
        $matiere['stock_actuel'] = $nouveauStock;
        $matiere['alerte'] = $nouveauStock <= (float)$matiere['stock_seuil'];

        return Result::created([
            'data' => $matiere,
            'mouvement' => $mouvementModel->find($mouvementModel->getInsertID()),
        ]);
    }

    public function decrementStock(string $matiereId, float $quantite, ?string $referenceType = null, ?string $referenceId = null): void
    {
        if ($quantite <= 0) return;
        $matiere = (new MatiereModel())->find($matiereId);
        if (!$matiere) return;
        $nouveau = max(0, (float)$matiere['stock_actuel'] - $quantite);
        (new MatiereModel())->update($matiereId, ['stock_actuel' => $nouveau]);
        (new MouvementStockModel())->insert([
            'matiere_id' => $matiereId,
            'type' => 'sortie',
            'quantite' => $quantite,
            'motif' => 'Consommation production',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function alertes(): Result
    {
        $model = new MatiereModel();
        $all = $model->findAll();
        $alertes = array_values(array_filter($all, static fn($m) => (float)($m['stock_actuel'] ?? 0) <= (float)($m['stock_seuil'] ?? 0)));
        return Result::ok(['data' => $alertes, 'total' => count($alertes)]);
    }
}