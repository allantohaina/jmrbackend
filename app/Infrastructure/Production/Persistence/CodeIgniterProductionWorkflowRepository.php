<?php

namespace App\Infrastructure\Production\Persistence;

use App\Domain\Production\Workflow\ProductionWorkflow;
use App\Domain\Production\Workflow\ProductionWorkflowRepository;
use App\Models\ProductionWorkflowModel;

class CodeIgniterProductionWorkflowRepository implements ProductionWorkflowRepository
{
    private readonly ProductionWorkflowModel $model;

    public function __construct(?ProductionWorkflowModel $model = null)
    {
        $this->model = $model ?? new ProductionWorkflowModel();
    }

    public function save(ProductionWorkflow $workflow): void
    {
        $data = [
            'id' => $workflow->id,
            'project_id' => $workflow->projectId,
            'name' => $workflow->name,
            'workflow_type' => $workflow->workflowType,
            'client_name' => $workflow->clientName,
            'approval_date' => $workflow->approvalDate,
            'delivery_date' => $workflow->deliveryDate,
            'launch_date' => $workflow->launchDate,
            'status' => $workflow->status,
            'current_step_id' => $workflow->currentStepId,
            'last_validated_step_id' => $workflow->lastValidatedStepId,
            'styles' => json_encode($workflow->styles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'measurements' => $workflow->measurements !== null
                ? json_encode($workflow->measurements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'production_notes' => $workflow->productionNotes,
            'steps' => json_encode($workflow->steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'history' => json_encode($workflow->history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'rollback_context' => $workflow->rollbackContext !== null
                ? json_encode($workflow->rollbackContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'created_at' => $workflow->createdAt,
            'updated_at' => $workflow->updatedAt,
        ];

        if ($this->model->find($workflow->id)) {
            $this->model->update($workflow->id, $data);
        } else {
            $this->model->insert($data);
        }
    }

    public function findById(string $id): ?ProductionWorkflow
    {
        $data = $this->model->find($id);
        if (!$data) {
            return null;
        }

        return $this->toDomain($data);
    }

    public function findByProjectId(string $projectId): array
    {
        $results = $this->model->where('project_id', $projectId)->orderBy('updated_at', 'DESC')->findAll();

        return array_map([$this, 'toDomain'], $results);
    }

    public function findAll(): array
    {
        $results = $this->model->orderBy('updated_at', 'DESC')->findAll();

        return array_map([$this, 'toDomain'], $results);
    }

    public function delete(string $id): void
    {
        $this->model->delete($id);
    }

    private function toDomain(array $data): ProductionWorkflow
    {
        return new ProductionWorkflow(
            $data['id'],
            $data['project_id'] ?? null,
            $data['name'],
            $data['workflow_type'] ?? ProductionWorkflow::TYPE_PRODUCTION_PLAN,
            $data['client_name'] ?? null,
            $data['approval_date'] ?? null,
            $data['delivery_date'] ?? null,
            $data['launch_date'] ?? null,
            $data['status'],
            $data['current_step_id'] ?? null,
            $data['last_validated_step_id'] ?? null,
            $this->decodeJsonArray($data['styles'] ?? '[]'),
            $this->decodeJsonNullableArray($data['measurements'] ?? null),
            $data['production_notes'] ?? null,
            $this->decodeJsonArray($data['steps'] ?? '[]'),
            $this->decodeJsonArray($data['history'] ?? '[]'),
            $this->decodeJsonNullableArray($data['rollback_context'] ?? null),
            $data['created_at'],
            $data['updated_at']
        );
    }

    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decodeJsonNullableArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
