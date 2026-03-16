<?php

namespace App\Domain\Production\Checklist;

interface ProductionChecklistRepository
{
    public function save(ProductionChecklist $checklist): void;
    public function findById(string $id): ?ProductionChecklist;
    public function findByProjectId(string $projectId): array;
    public function delete(string $id): void;
}
