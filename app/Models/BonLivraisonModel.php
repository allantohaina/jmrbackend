<?php

namespace App\Models;

use CodeIgniter\Model;

class BonLivraisonModel extends Model
{
    protected $table = 'bons_livraison';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $useSoftDeletes = false;
    protected $returnType = 'array';

    protected $allowedFields = [
        'id', 'commande_id', 'numero', 'date_livraison',
        'destinataire', 'articles', 'statut', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'numero' => 'required|max_length[50]',
        'commande_id' => 'required',
        'destinataire' => 'required|max_length[255]',
    ];

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = \App\Traits\UuidTrait::uuidV4();
        }
        return $data;
    }

    public function getWithCommande(): array
    {
        return $this->select('bons_livraison.*, commandes.numero as commande_numero, commandes.designation as commande_designation')
            ->join('commandes', 'commandes.id = bons_livraison.commande_id', 'left')
            ->orderBy('bons_livraison.created_at', 'DESC')
            ->findAll();
    }
}
