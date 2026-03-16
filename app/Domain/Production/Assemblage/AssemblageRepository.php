<?php

namespace App\Domain\Production\Assemblage;

interface AssemblageRepository
{
    public function save(Assemblage $assemblage): void;
    public function findById(string $id): ?Assemblage;
    public function findByProjectId(string $projectId): array;
    public function delete(string $id): void;
}
