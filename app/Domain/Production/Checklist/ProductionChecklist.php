<?php

namespace App\Domain\Production\Checklist;

use App\Traits\UuidTrait;

class ProductionChecklist
{
    use UuidTrait;

    public const TYPE_COUPE_ASSEMBLAGE = 'CL-P-01';
    public const TYPE_CONTROLE_QUALITE = 'CL-P-02';
    public const TYPE_VALIDATION_FINALE = 'CL-P-03';

    public const TYPE_QUALIFICATION_PROSPECT = 'CL-C-01';
    public const TYPE_RDV_CADRAGE = 'CL-C-02';
    public const TYPE_DEVIS_ACOMPTE = 'CL-C-03';
    public const TYPE_VALIDATION_PRE_PROD = 'CL-C-04';

    public const TYPE_PREPARATION_EXPEDITION = 'CL-L-01';
    public const TYPE_DOCUMENTS_SORTIE = 'CL-L-02';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public function __construct(
        public readonly string $id,
        public readonly ?string $projectId,
        public readonly string $type,
        public string $status,
        public array $items,
        public readonly string $createdAt,
        public string $updatedAt
    ) {
    }

    public static function create(string $type, ?string $projectId = null): self
    {
        $items = self::getDefaultItemsForType($type);
        $now = date('Y-m-d H:i:s');

        return new self(
            self::uuidV4(),
            $projectId,
            $type,
            self::STATUS_PENDING,
            $items,
            $now,
            $now
        );
    }

    private static function getDefaultItemsForType(string $type): array
    {
        return match ($type) {
            self::TYPE_COUPE_ASSEMBLAGE => [
                ['label' => 'Vérification métrage / surplus prévu', 'checked' => false, 'value' => null],
                ['label' => 'Contrôle première coupe', 'checked' => false, 'value' => null],
                ['label' => 'Suivi assemblage', 'checked' => false, 'value' => null],
                ['label' => 'Contrôle intermédiaire sur échantillons', 'checked' => false, 'value' => null],
            ],
            self::TYPE_CONTROLE_QUALITE => [
                ['label' => 'Vérification tailles & quantités', 'checked' => false, 'value' => null],
                ['label' => 'Vérification finitions & coutures', 'checked' => false, 'value' => null],
                ['label' => 'Vérification emballage & étiquettes', 'checked' => false, 'value' => null],
                ['label' => 'Photos fin production', 'checked' => false, 'value' => []],
            ],
            self::TYPE_VALIDATION_FINALE => [
                ['label' => 'Comptage double confirmé', 'checked' => false, 'value' => null],
                ['label' => 'Coût réel / marge validés', 'checked' => false, 'value' => null],
                ['label' => 'Solde client encaissé', 'checked' => false, 'value' => null],
                ['label' => 'Statut interne : prêt pour remise au transitaire', 'checked' => false, 'value' => null],
            ],
            self::TYPE_QUALIFICATION_PROSPECT => [
                ['label' => 'Formulaire rempli', 'checked' => false, 'value' => null],
                ['label' => 'Volume / budget / délai validé', 'checked' => false, 'value' => null],
                ['label' => 'Scoring interne A/B/C', 'checked' => false, 'value' => null],
                ['label' => 'Responsable commercial assigné', 'checked' => false, 'value' => null],
            ],
            self::TYPE_RDV_CADRAGE => [
                ['label' => 'Compte rendu (RDS) rédigé', 'checked' => false, 'value' => null],
                ['label' => 'Besoins exacts client listés', 'checked' => false, 'value' => null],
                ['label' => 'Validation prototype si nécessaire', 'checked' => false, 'value' => null],
                ['label' => 'Confirmation écrite client', 'checked' => false, 'value' => null],
            ],
            self::TYPE_DEVIS_ACOMPTE => [
                ['label' => 'Devis validé (marge et coût)', 'checked' => false, 'value' => null],
                ['label' => 'Acompte reçu', 'checked' => false, 'value' => null],
                ['label' => 'Conditions générales acceptées', 'checked' => false, 'value' => null],
                ['label' => 'Fiche technique attachée', 'checked' => false, 'value' => null],
            ],
            self::TYPE_VALIDATION_PRE_PROD => [
                ['label' => 'Prototype validé', 'checked' => false, 'value' => null],
                ['label' => 'Planification atelier confirmée', 'checked' => false, 'value' => null],
                ['label' => 'Matières disponibles ou commandées', 'checked' => false, 'value' => null],
                ['label' => 'Statut interne : prêt pour production', 'checked' => false, 'value' => null],
            ],
            self::TYPE_PREPARATION_EXPEDITION => [
                ['label' => 'Colisage vérifié (Packing List)', 'checked' => false, 'value' => null],
                ['label' => 'Étiquetage cartons conforme', 'checked' => false, 'value' => null],
                ['label' => 'Poids et dimensions relevés', 'checked' => false, 'value' => null],
            ],
            self::TYPE_DOCUMENTS_SORTIE => [
                ['label' => 'Facture finale émise', 'checked' => false, 'value' => null],
                ['label' => 'Bon de livraison signé', 'checked' => false, 'value' => null],
                ['label' => 'Remis au transitaire / transporteur', 'checked' => false, 'value' => null],
            ],
            default => [],
        };
    }

    public function updateItem(int $index, bool $checked, $value = null): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['checked'] = $checked;
            
            if ($value !== null) {
                // Si la valeur actuelle est un tableau et la nouvelle valeur n'est pas un tableau, on l'ajoute
                if (is_array($this->items[$index]['value']) && !is_array($value)) {
                    if (!in_array($value, $this->items[$index]['value'])) {
                        $this->items[$index]['value'][] = $value;
                    }
                } else {
                    $this->items[$index]['value'] = $value;
                }
            }
            
            $this->updatedAt = date('Y-m-d H:i:s');
            $this->updateStatus();
        }
    }

    public function removeItem(int $index, $value): void
    {
        if (isset($this->items[$index]) && is_array($this->items[$index]['value'])) {
            $key = array_search($value, $this->items[$index]['value']);
            if ($key !== false) {
                unset($this->items[$index]['value'][$key]);
                $this->items[$index]['value'] = array_values($this->items[$index]['value']);
                $this->updatedAt = date('Y-m-d H:i:s');
                $this->updateStatus();
            }
        }
    }

    private function updateStatus(): void
    {
        $checkedCount = count(array_filter($this->items, fn($item) => $item['checked']));
        if ($checkedCount === 0) {
            $this->status = self::STATUS_PENDING;
        } elseif ($checkedCount === count($this->items)) {
            $this->status = self::STATUS_COMPLETED;
        } else {
            $this->status = self::STATUS_IN_PROGRESS;
        }
    }
}
