<?php

namespace App\Domain\Production\Workflow;

interface ProductionWorkflowRepository
{
    public function save(ProductionWorkflow $workflow): void;

    public function findById(string $id): ?ProductionWorkflow;

    public function findByProjectId(string $projectId): array;

    public function findAll(): array;

    public function delete(string $id): void;
}
