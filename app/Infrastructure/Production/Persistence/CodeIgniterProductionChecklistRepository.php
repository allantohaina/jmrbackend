<?php

namespace App\Infrastructure\Production\Persistence;

use App\Domain\Production\Checklist\ProductionChecklist;
use App\Domain\Production\Checklist\ProductionChecklistRepository;
use App\Models\ProductionChecklistModel;

class CodeIgniterProductionChecklistRepository implements ProductionChecklistRepository
{
    private readonly ProductionChecklistModel $model;

    public function __construct(
        ?ProductionChecklistModel $model = null
    ) {
        $this->model = $model ?? new ProductionChecklistModel();
    }

    public function save(ProductionChecklist $checklist): void
    {
        $data = [
            'id' => $checklist->id,
            'project_id' => $checklist->projectId,
            'type' => $checklist->type,
            'status' => $checklist->status,
            'items' => json_encode($checklist->items),
            'created_at' => $checklist->createdAt,
            'updated_at' => $checklist->updatedAt,
        ];

        if ($this->model->find($checklist->id)) {
            $this->model->update($checklist->id, $data);
        } else {
            $this->model->insert($data);
        }
    }

    public function findById(string $id): ?ProductionChecklist
    {
        $data = $this->model->find($id);
        if (!$data) {
            return null;
        }

        return $this->toDomain($data);
    }

    public function findByProjectId(string $projectId): array
    {
        $results = $this->model->where('project_id', $projectId)->findAll();
        return array_map([$this, 'toDomain'], $results);
    }

    public function delete(string $id): void
    {
        $this->model->delete($id);
    }

    private function toDomain(array $data): ProductionChecklist
    {
        return new ProductionChecklist(
            $data['id'],
            $data['project_id'],
            $data['type'],
            $data['status'],
            json_decode($data['items'], true),
            $data['created_at'],
            $data['updated_at']
        );
    }
}
