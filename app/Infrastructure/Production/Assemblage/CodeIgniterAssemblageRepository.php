<?php

namespace App\Infrastructure\Production\Assemblage;

use App\Domain\Production\Assemblage\Assemblage;
use App\Domain\Production\Assemblage\AssemblageRepository;
use App\Models\AssemblageModel;

class CodeIgniterAssemblageRepository implements AssemblageRepository
{
    private readonly AssemblageModel $model;

    public function __construct(?AssemblageModel $model = null)
    {
        $this->model = $model ?? new AssemblageModel();
    }

    public function save(Assemblage $assemblage): void
    {
        $data = $this->toDb($assemblage);
        if ($this->model->find($assemblage->id)) {
            $this->model->update($assemblage->id, $data);
        } else {
            $this->model->insert($data);
        }
    }

    public function findById(string $id): ?Assemblage
    {
        $data = $this->model->find($id);
        return $data ? $this->toDomain($data) : null;
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

    private function toDomain(array $data): Assemblage
    {
        return new Assemblage(
            $data['id'],
            $data['project_id'],
            $data['name'],
            $data['status'],
            $data['details'],
            $data['created_at'],
            $data['updated_at']
        );
    }

    private function toDb(Assemblage $assemblage): array
    {
        return [
            'id' => $assemblage->id,
            'project_id' => $assemblage->projectId,
            'name' => $assemblage->name,
            'status' => $assemblage->status,
            'details' => $assemblage->details,
            'created_at' => $assemblage->createdAt,
            'updated_at' => $assemblage->updatedAt,
        ];
    }
}
