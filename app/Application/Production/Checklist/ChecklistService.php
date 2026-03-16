<?php

namespace App\Application\Production\Checklist;

use App\Application\Shared\Result;
use App\Domain\Production\Checklist\ProductionChecklist;
use App\Domain\Production\Checklist\ProductionChecklistRepository;
use App\Infrastructure\Production\Persistence\CodeIgniterProductionChecklistRepository;

class ChecklistService
{
    private readonly ProductionChecklistRepository $repository;

    public function __construct(?ProductionChecklistRepository $repository = null)
    {
        $this->repository = $repository ?? new CodeIgniterProductionChecklistRepository();
    }

    public function create(string $type, ?string $projectId = null): Result
    {
        if (!in_array($type, [
            ProductionChecklist::TYPE_COUPE_ASSEMBLAGE,
            ProductionChecklist::TYPE_CONTROLE_QUALITE,
            ProductionChecklist::TYPE_VALIDATION_FINALE,
            ProductionChecklist::TYPE_QUALIFICATION_PROSPECT,
            ProductionChecklist::TYPE_RDV_CADRAGE,
            ProductionChecklist::TYPE_DEVIS_ACOMPTE,
            ProductionChecklist::TYPE_VALIDATION_PRE_PROD,
            ProductionChecklist::TYPE_PREPARATION_EXPEDITION,
            ProductionChecklist::TYPE_DOCUMENTS_SORTIE,
        ])) {
            return Result::fail(['error' => 'Type de check-list invalide'], 400);
        }

        $checklist = ProductionChecklist::create($type, $projectId);
        $this->repository->save($checklist);

        return Result::created($this->toArray($checklist));
    }

    public function initializeForProject(string $projectId): Result
    {
        $types = [
            ProductionChecklist::TYPE_COUPE_ASSEMBLAGE,
            ProductionChecklist::TYPE_CONTROLE_QUALITE,
            ProductionChecklist::TYPE_VALIDATION_FINALE
        ];

        $results = [];
        foreach ($types as $type) {
            $checklist = ProductionChecklist::create($type, $projectId);
            $this->repository->save($checklist);
            $results[] = $this->toArray($checklist);
        }

        return Result::created($results);
    }

    public function initializeCommandForProject(string $projectId): Result
    {
        $types = [
            ProductionChecklist::TYPE_QUALIFICATION_PROSPECT,
            ProductionChecklist::TYPE_RDV_CADRAGE,
            ProductionChecklist::TYPE_DEVIS_ACOMPTE,
            ProductionChecklist::TYPE_VALIDATION_PRE_PROD,
        ];

        $results = [];
        foreach ($types as $type) {
            $checklist = ProductionChecklist::create($type, $projectId);
            $this->repository->save($checklist);
            $results[] = $this->toArray($checklist);
        }

        return Result::created($results);
    }

    public function initializeDeliveryForProject(string $projectId): Result
    {
        $types = [
            ProductionChecklist::TYPE_PREPARATION_EXPEDITION,
            ProductionChecklist::TYPE_DOCUMENTS_SORTIE,
        ];

        $results = [];
        foreach ($types as $type) {
            $checklist = ProductionChecklist::create($type, $projectId);
            $this->repository->save($checklist);
            $results[] = $this->toArray($checklist);
        }

        return Result::created($results);
    }

    public function updateItem(string $id, int $itemIndex, bool $checked, $value = null): Result
    {
        $checklist = $this->repository->findById($id);
        if (!$checklist) {
            return Result::fail(['error' => 'Check-list non trouvée'], 404);
        }

        $checklist->updateItem($itemIndex, $checked, $value);
        $this->repository->save($checklist);

        return Result::ok($this->toArray($checklist));
    }

    public function removeItemValue(string $id, int $itemIndex, $value): Result
    {
        $checklist = $this->repository->findById($id);
        if (!$checklist) {
            return Result::fail(['error' => 'Check-list non trouvée'], 404);
        }

        $checklist->removeItem($itemIndex, $value);
        $this->repository->save($checklist);

        return Result::ok($this->toArray($checklist));
    }

    public function getByProject(string $projectId): Result
    {
        $checklists = $this->repository->findByProjectId($projectId);
        return Result::ok(array_map([$this, 'toArray'], $checklists));
    }

    public function getById(string $id): Result
    {
        $checklist = $this->repository->findById($id);
        if (!$checklist) {
            return Result::fail(['error' => 'Check-list non trouvée'], 404);
        }

        return Result::ok($this->toArray($checklist));
    }

    private function toArray(ProductionChecklist $checklist): array
    {
        return [
            'id' => $checklist->id,
            'project_id' => $checklist->projectId,
            'type' => $checklist->type,
            'status' => $checklist->status,
            'items' => $checklist->items,
            'created_at' => $checklist->createdAt,
            'updated_at' => $checklist->updatedAt,
        ];
    }
}
