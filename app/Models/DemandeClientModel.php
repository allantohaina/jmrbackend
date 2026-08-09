<?php

namespace App\Models;

use CodeIgniter\Model;

class DemandeClientModel extends Model
{
    protected $table = 'demandes_client';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'id', 'nom_client', 'entreprise', 'email', 'telephone',
        'description', 'statut', 'cotation_id', 'date_reception',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'nom_client' => 'required|min_length[2]|max_length[255]',
        'description' => 'required|min_length[5]',
        'statut' => 'required|in_list[Nouvelle,En cours d\'étude,Convertie en cotation,Refusée]',
    ];

    protected $beforeInsert = ['generateUUID', 'defaults'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        }
        return $data;
    }

    protected function defaults(array $data): array
    {
        if (empty($data['data']['date_reception'])) {
            $data['data']['date_reception'] = date('Y-m-d H:i:s');
        }
        if (empty($data['data']['statut'])) {
            $data['data']['statut'] = 'Nouvelle';
        }
        return $data;
    }

    public function getAllWithDetails(): array
    {
        return $this->select('demandes_client.*, q.status as cotation_statut, q.amount as cotation_montant')
            ->join('quotes q', 'q.id = demandes_client.cotation_id', 'left')
            ->orderBy('demandes_client.date_reception', 'DESC')
            ->findAll();
    }

    public function countByStatut(): array
    {
        $rows = $this->select('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->findAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['statut']] = (int)($r['total'] ?? 0);
        }
        return $result;
    }
}
