<?php

namespace App\Application\Production\Assemblage;

use App\Application\Shared\Result;
use App\Domain\Production\Assemblage\Assemblage;
use App\Domain\Production\Assemblage\AssemblageRepository;
use App\Infrastructure\Production\Assemblage\CodeIgniterAssemblageRepository;

class AssemblageService
{
    private readonly AssemblageRepository $repository;

    public function __construct(?AssemblageRepository $repository = null)
    {
        $this->repository = $repository ?? new CodeIgniterAssemblageRepository();
    }

    public function create(array $data): Result
    {
        $projectId = $data['project_id'] ?? null;
        $name = $data['name'] ?? null;

        if (!$projectId || !$name) {
            return Result::fail(['error' => 'Project ID and name are required.'], 400);
        }

        $assemblage = Assemblage::create($projectId, $name, $data['details'] ?? null);
        $this->repository->save($assemblage);

        return Result::created($this->toArray($assemblage));
    }

    public function update(string $id, array $data): Result
    {
        $assemblage = $this->repository->findById($id);
        if (!$assemblage) {
            return Result::fail(['error' => 'Assemblage not found.'], 404);
        }

        $assemblage->name = $data['name'] ?? $assemblage->name;
        $assemblage->status = $data['status'] ?? $assemblage->status;
        $assemblage->details = $data['details'] ?? $assemblage->details;
        $assemblage->updatedAt = date('Y-m-d H:i:s');

        $this->repository->save($assemblage);

        return Result::ok($this->toArray($assemblage));
    }

    public function delete(string $id): Result
    {
        $assemblage = $this->repository->findById($id);
        if (!$assemblage) {
            return Result::fail(['error' => 'Assemblage not found.'], 404);
        }

        $this->repository->delete($id);
        return Result::ok(['message' => 'Assemblage deleted successfully.']);
    }

    public function getById(string $id): Result
    {
        $assemblage = $this->repository->findById($id);
        if (!$assemblage) {
            return Result::fail(['error' => 'Assemblage not found.'], 404);
        }
        return Result::ok($this->toArray($assemblage));
    }

    public function getByProject(string $projectId): Result
    {
        $assemblages = $this->repository->findByProjectId($projectId);
        return Result::ok(array_map([$this, 'toArray'], $assemblages));
    }

    private function toArray(Assemblage $assemblage): array
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
