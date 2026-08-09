<?php

namespace App\Models;

use CodeIgniter\Model;

class ProduitModel extends Model
{
    protected $table = 'produits';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'id', 'nom', 'categorie',
        'conso_tissu_unitaire', 'conso_tissu_par_taille',
        'niveau_difficulte_defaut', 'moq',
        'cout_matiere_defaut', 'cout_mo_par_piece', 'frais_generaux_pct',
        'description', 'photo_url',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'nom' => 'required|min_length[2]|max_length[255]',
        'categorie' => 'permit_empty|max_length[100]',
        'conso_tissu_unitaire' => 'required|decimal',
        'niveau_difficulte_defaut' => 'required|decimal',
        'moq' => 'required|integer',
    ];

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        }
        if (isset($data['data']['conso_tissu_par_taille']) && is_array($data['data']['conso_tissu_par_taille'])) {
            $data['data']['conso_tissu_par_taille'] = json_encode($data['data']['conso_tissu_par_taille'], JSON_UNESCAPED_UNICODE);
        }
        return $data;
    }

    protected function beforeUpdate(array $data): array
    {
        if (isset($data['data']['conso_tissu_par_taille']) && is_array($data['data']['conso_tissu_par_taille'])) {
            $data['data']['conso_tissu_par_taille'] = json_encode($data['data']['conso_tissu_par_taille'], JSON_UNESCAPED_UNICODE);
        }
        return $data;
    }

    public function findWithDecoded($id): ?array
    {
        $row = $this->find($id);
        if (!$row) return null;
        if (!empty($row['conso_tissu_par_taille']) && is_string($row['conso_tissu_par_taille'])) {
            $decoded = json_decode($row['conso_tissu_par_taille'], true);
            $row['conso_tissu_par_taille'] = is_array($decoded) ? $decoded : null;
        }
        return $row;
    }

    public function findAllWithDecoded(int $limit = 0, int $offset = 0): array
    {
        $rows = $this->orderBy('created_at', 'DESC')->findAll($limit, $offset);
        foreach ($rows as &$row) {
            if (!empty($row['conso_tissu_par_taille']) && is_string($row['conso_tissu_par_taille'])) {
                $decoded = json_decode($row['conso_tissu_par_taille'], true);
                $row['conso_tissu_par_taille'] = is_array($decoded) ? $decoded : null;
            }
        }
        return $rows;
    }

    public function listCategories(): array
    {
        $rows = $this->select('categorie')
            ->where('categorie IS NOT NULL AND categorie <> ""')
            ->distinct()
            ->orderBy('categorie', 'ASC')
            ->findAll();
        return array_values(array_filter(array_map(fn($r) => $r['categorie'] ?? null, $rows)));
    }
}
