<?php

namespace App\Application\Production\Workflow;

use App\Application\Shared\Result;
use App\Domain\Production\Workflow\ProductionWorkflow;
use App\Domain\Production\Workflow\ProductionWorkflowRepository;
use App\Infrastructure\Production\Persistence\CodeIgniterProductionWorkflowRepository;

class ProductionWorkflowService
{
    private readonly ProductionWorkflowRepository $repository;

    public function __construct(?ProductionWorkflowRepository $repository = null)
    {
        $this->repository = $repository ?? new CodeIgniterProductionWorkflowRepository();
    }

    public function create(array $data, ?array $actor = null): Result
    {
        $workflow = ProductionWorkflow::create(
            $this->stringOrNull($data['name'] ?? null),
            $this->stringOrNull($data['project_id'] ?? null),
            $this->normalizeSteps($data['steps'] ?? []),
            $this->normalizeWorkflowType($data['workflow_type'] ?? $data['type'] ?? null),
            $this->stringOrNull($data['client_name'] ?? $data['client'] ?? null),
            $this->dateOrNull($data['approval_date'] ?? $data['approv_date'] ?? $data['date'] ?? null),
            $this->dateOrNull($data['delivery_date'] ?? $data['d_date'] ?? null),
            $this->dateOrNull($data['launch_date'] ?? $data['launch'] ?? null),
            $this->normalizeStyles($data['styles'] ?? []),
            $this->normalizeMeasurements($data['measurements'] ?? null),
            $this->stringOrNull($data['production_notes'] ?? $data['notes'] ?? null)
        );

        if ($this->hasDependencyCycle($workflow->steps)) {
            return Result::fail(['error' => 'Les dependances des etapes contiennent une boucle.'], 400);
        }

        $this->recalculateWorkflowState($workflow);
        $workflow->history[] = $this->buildHistoryEntry('create', null, [
            'name' => $workflow->name,
            'project_id' => $workflow->projectId,
            'workflow_type' => $workflow->workflowType,
            'styles_count' => count($workflow->styles),
        ], $actor);
        $workflow->updatedAt = date('Y-m-d H:i:s');

        $this->repository->save($workflow);

        return Result::created($this->toArray($workflow));
    }

    public function index(?string $projectId = null, mixed $workflowType = null, mixed $clientName = null): Result
    {
        $workflows = $projectId
            ? $this->repository->findByProjectId($projectId)
            : $this->repository->findAll();

        $typeFilter = $this->stringOrNull($workflowType);
        $clientFilter = $this->stringOrNull($clientName);

        if ($typeFilter !== null) {
            $normalizedType = $this->normalizeWorkflowType($typeFilter);
            $workflows = array_values(array_filter(
                $workflows,
                static fn(ProductionWorkflow $workflow): bool => $workflow->workflowType === $normalizedType
            ));
        }

        if ($clientFilter !== null) {
            $normalizedClient = strtolower($clientFilter);
            $workflows = array_values(array_filter(
                $workflows,
                static fn(ProductionWorkflow $workflow): bool => $workflow->clientName !== null
                    && str_contains(strtolower($workflow->clientName), $normalizedClient)
            ));
        }

        return Result::ok(array_map([$this, 'toArray'], $workflows));
    }

    public function getById(string $id): Result
    {
        $workflow = $this->repository->findById($id);
        if (!$workflow) {
            return Result::fail(['error' => 'Processus introuvable'], 404);
        }

        return Result::ok($this->toArray($workflow));
    }

    public function update(string $id, array $data, ?array $actor = null): Result
    {
        $workflow = $this->repository->findById($id);
        if (!$workflow) {
            return Result::fail(['error' => 'Processus introuvable'], 404);
        }

        $workflow->name = $this->stringOrNull($data['name'] ?? $workflow->name) ?? $workflow->name;
        $workflow->workflowType = array_key_exists('workflow_type', $data) || array_key_exists('type', $data)
            ? $this->normalizeWorkflowType($data['workflow_type'] ?? $data['type'] ?? null)
            : $workflow->workflowType;
        $workflow->clientName = array_key_exists('client_name', $data) || array_key_exists('client', $data)
            ? $this->stringOrNull($data['client_name'] ?? $data['client'] ?? null)
            : $workflow->clientName;
        $workflow->approvalDate = array_key_exists('approval_date', $data) || array_key_exists('approv_date', $data) || array_key_exists('date', $data)
            ? $this->dateOrNull($data['approval_date'] ?? $data['approv_date'] ?? $data['date'] ?? null)
            : $workflow->approvalDate;
        $workflow->deliveryDate = array_key_exists('delivery_date', $data) || array_key_exists('d_date', $data)
            ? $this->dateOrNull($data['delivery_date'] ?? $data['d_date'] ?? null)
            : $workflow->deliveryDate;
        $workflow->launchDate = array_key_exists('launch_date', $data) || array_key_exists('launch', $data)
            ? $this->dateOrNull($data['launch_date'] ?? $data['launch'] ?? null)
            : $workflow->launchDate;
        $workflow->productionNotes = array_key_exists('production_notes', $data) || array_key_exists('notes', $data)
            ? $this->stringOrNull($data['production_notes'] ?? $data['notes'] ?? null)
            : $workflow->productionNotes;
        $workflow->projectId = array_key_exists('project_id', $data)
            ? $this->stringOrNull($data['project_id'])
            : $workflow->projectId;

        if (array_key_exists('styles', $data)) {
            $workflow->styles = $this->normalizeStyles($data['styles'], $workflow->styles);
        }

        if (array_key_exists('measurements', $data)) {
            $workflow->measurements = $this->normalizeMeasurements($data['measurements']);
        }

        if (array_key_exists('steps', $data)) {
            $workflow->steps = $this->normalizeSteps($data['steps'], $workflow->steps);
        }

        if ($this->hasDependencyCycle($workflow->steps)) {
            return Result::fail(['error' => 'Les dependances des etapes contiennent une boucle.'], 400);
        }

        $this->recalculateWorkflowState($workflow);
        $workflow->history[] = $this->buildHistoryEntry('update', null, [
            'name' => $workflow->name,
            'workflow_type' => $workflow->workflowType,
            'client_name' => $workflow->clientName,
            'styles_count' => count($workflow->styles),
            'steps_count' => count($workflow->steps),
        ], $actor);
        $workflow->updatedAt = date('Y-m-d H:i:s');

        $this->repository->save($workflow);

        return Result::ok($this->toArray($workflow));
    }

    public function transition(string $id, array $data, ?array $actor = null): Result
    {
        $workflow = $this->repository->findById($id);
        if (!$workflow) {
            return Result::fail(['error' => 'Processus introuvable'], 404);
        }

        $action = $this->stringOrNull($data['action'] ?? null);
        if (!$action) {
            return Result::fail(['error' => 'Action requise'], 400);
        }

        $stepId = $this->stringOrNull($data['step_id'] ?? null);
        $now = date('Y-m-d H:i:s');

        switch ($action) {
            case 'complete_step':
                if (!$stepId) {
                    return Result::fail(['error' => 'step_id requis'], 400);
                }
                $stepIndex = $this->findStepIndex($workflow->steps, $stepId);
                if ($stepIndex === null) {
                    return Result::fail(['error' => 'Etape introuvable'], 404);
                }
                $step = $workflow->steps[$stepIndex];
                $step['status'] = !empty($step['requires_validation'])
                    ? ProductionWorkflow::STEP_STATUS_AWAITING_VALIDATION
                    : ProductionWorkflow::STEP_STATUS_VALIDATED;
                if ($step['status'] === ProductionWorkflow::STEP_STATUS_VALIDATED) {
                    $step['validated_at'] = $now;
                    $step['validated_by'] = $this->actorLabel($actor);
                }
                $workflow->steps[$stepIndex] = $this->syncStepDefaults($step, $stepIndex + 1);
                $workflow->history[] = $this->buildHistoryEntry($action, $stepId, [], $actor);
                break;

            case 'approve_step':
                if (!$stepId) {
                    return Result::fail(['error' => 'step_id requis'], 400);
                }
                $stepIndex = $this->findStepIndex($workflow->steps, $stepId);
                if ($stepIndex === null) {
                    return Result::fail(['error' => 'Etape introuvable'], 404);
                }
                $step = $workflow->steps[$stepIndex];
                if (!$this->canValidateStep($step, $actor)) {
                    return Result::forbidden(['error' => 'Vous n\'avez pas le droit de valider cette etape']);
                }
                $step['status'] = ProductionWorkflow::STEP_STATUS_VALIDATED;
                $step['validated_at'] = $now;
                $step['validated_by'] = $this->actorLabel($actor);
                $workflow->steps[$stepIndex] = $this->syncStepDefaults($step, $stepIndex + 1);
                $workflow->history[] = $this->buildHistoryEntry($action, $stepId, [], $actor);
                break;

            case 'reject_step':
                if (!$stepId) {
                    return Result::fail(['error' => 'step_id requis'], 400);
                }
                $stepIndex = $this->findStepIndex($workflow->steps, $stepId);
                if ($stepIndex === null) {
                    return Result::fail(['error' => 'Etape introuvable'], 404);
                }
                $step = $workflow->steps[$stepIndex];
                if (!$this->canValidateStep($step, $actor)) {
                    return Result::forbidden(['error' => 'Vous n\'avez pas le droit de rejeter cette etape']);
                }
                $step['status'] = ProductionWorkflow::STEP_STATUS_NEEDS_CORRECTION;
                $step['rejected_at'] = $now;
                $step['rejected_by'] = $this->actorLabel($actor);
                $step['rejection_reason'] = $this->stringOrNull($data['reason'] ?? null);
                $step['correction_notes'] = $this->stringOrNull($data['correction_notes'] ?? null);
                $workflow->steps[$stepIndex] = $this->syncStepDefaults($step, $stepIndex + 1);
                $workflow->history[] = $this->buildHistoryEntry($action, $stepId, [
                    'reason' => $step['rejection_reason'],
                    'correction_notes' => $step['correction_notes'],
                ], $actor);
                break;

            case 'major_rollback':
                if ($this->actorRole($actor) !== 'admin') {
                    return Result::forbidden(['error' => 'Seul un administrateur peut lancer un rollback majeur']);
                }
                $reason = $this->stringOrNull($data['reason'] ?? null);
                if (!$reason) {
                    return Result::fail(['error' => 'La raison du rollback est requise'], 400);
                }
                $stepsToRedo = $this->normalizeStringArray($data['steps_to_redo'] ?? []);
                $impactedRoles = $this->normalizeStringArray($data['impacted_roles'] ?? []);
                $returnToStepId = $this->stringOrNull($data['return_to_step_id'] ?? null) ?? $workflow->lastValidatedStepId;

                foreach ($workflow->steps as $index => $step) {
                    if (in_array($step['id'], $stepsToRedo, true)) {
                        $workflow->steps[$index]['status'] = ProductionWorkflow::STEP_STATUS_NEEDS_CORRECTION;
                        $workflow->steps[$index]['rejection_reason'] = $reason;
                        $workflow->steps[$index]['correction_notes'] = $reason;
                        $workflow->steps[$index]['rejected_at'] = $now;
                        $workflow->steps[$index]['rejected_by'] = $this->actorLabel($actor);
                    }
                }

                $workflow->rollbackContext = [
                    'reason' => $reason,
                    'steps_to_redo' => $stepsToRedo,
                    'impacted_roles' => $impactedRoles,
                    'return_to_step_id' => $returnToStepId,
                    'requested_at' => $now,
                    'requested_by' => $this->actorLabel($actor),
                ];
                $workflow->history[] = $this->buildHistoryEntry($action, null, $workflow->rollbackContext, $actor);
                break;

            default:
                return Result::fail(['error' => 'Action inconnue'], 400);
        }

        $this->recalculateWorkflowState($workflow, $stepId);
        $workflow->updatedAt = $now;

        $this->repository->save($workflow);

        return Result::ok($this->toArray($workflow));
    }

    private function toArray(ProductionWorkflow $workflow): array
    {
        $steps = $this->decorateSteps($workflow->steps, $workflow);
        $validatedSteps = array_values(array_filter($steps, static fn(array $step) => $step['status'] === ProductionWorkflow::STEP_STATUS_VALIDATED));
        $totalSteps = count($steps);
        $validatedCount = count($validatedSteps);

        return [
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
            'styles' => $workflow->styles,
            'measurements' => $workflow->measurements,
            'production_notes' => $workflow->productionNotes,
            'steps' => $steps,
            'history' => $workflow->history,
            'rollback_context' => $workflow->rollbackContext,
            'metrics' => [
                'total_steps' => $totalSteps,
                'validated_steps' => $validatedCount,
                'ready_steps' => count(array_filter($steps, static fn(array $step) => !empty($step['is_ready']))),
                'blocked_steps' => count(array_filter($steps, static fn(array $step) => !empty($step['is_blocked']))),
                'progress' => $totalSteps > 0 ? round(($validatedCount / $totalSteps) * 100, 1) : 0,
            ],
            'created_at' => $workflow->createdAt,
            'updated_at' => $workflow->updatedAt,
        ];
    }

    private function recalculateWorkflowState(ProductionWorkflow $workflow, ?string $focusStepId = null): void
    {
        $steps = $this->decorateSteps($workflow->steps, $workflow, false);
        $workflow->steps = $steps;

        $validatedSteps = array_values(array_filter($steps, static fn(array $step) => $step['status'] === ProductionWorkflow::STEP_STATUS_VALIDATED));
        usort($validatedSteps, static function (array $left, array $right): int {
            return strcmp((string) ($right['validated_at'] ?? ''), (string) ($left['validated_at'] ?? ''));
        });

        $workflow->lastValidatedStepId = $validatedSteps[0]['id'] ?? null;

        $awaitingValidation = $this->firstStepWithStatus($steps, ProductionWorkflow::STEP_STATUS_AWAITING_VALIDATION);
        if ($awaitingValidation !== null) {
            $workflow->currentStepId = $awaitingValidation['id'];
        } elseif ($this->hasNeedsCorrectionStep($steps)) {
            $workflow->currentStepId = $workflow->lastValidatedStepId;
        } else {
            $workflow->currentStepId = $this->firstAvailableStepId($steps);
        }

        if (empty($steps)) {
            $workflow->status = ProductionWorkflow::STATUS_DRAFT;
        } elseif ($this->hasNeedsCorrectionStep($steps)) {
            $workflow->status = ProductionWorkflow::STATUS_NEEDS_CORRECTION;
        } elseif ($validatedSteps !== [] && count($validatedSteps) === count($steps)) {
            $workflow->status = ProductionWorkflow::STATUS_COMPLETED;
        } else {
            $workflow->status = ProductionWorkflow::STATUS_ACTIVE;
        }

        $workflow->steps = $this->decorateSteps($workflow->steps, $workflow);
    }

    private function decorateSteps(array $steps, ProductionWorkflow $workflow, bool $decorateReady = true): array
    {
        $stepIds = array_map(static fn(array $step) => $step['id'], $steps);
        $validatedIds = [];
        foreach ($steps as $step) {
            if (($step['status'] ?? null) === ProductionWorkflow::STEP_STATUS_VALIDATED) {
                $validatedIds[] = $step['id'];
            }
        }

        foreach ($steps as $index => $step) {
            $dependsOn = $this->normalizeStringArray($step['depends_on'] ?? []);
            $nextStepIds = [];

            foreach ($steps as $candidate) {
                $candidateDependsOn = $this->normalizeStringArray($candidate['depends_on'] ?? []);
                if (in_array($step['id'], $candidateDependsOn, true)) {
                    $nextStepIds[] = $candidate['id'];
                }
            }

            $isReady = $this->dependenciesAreSatisfied($dependsOn, $validatedIds);
            $isBlocked = !$isReady && (($step['status'] ?? ProductionWorkflow::STEP_STATUS_PENDING) !== ProductionWorkflow::STEP_STATUS_VALIDATED);
            $steps[$index]['depends_on'] = array_values(array_intersect($dependsOn, $stepIds));
            $steps[$index]['next_step_ids'] = array_values(array_unique($nextStepIds));
            $steps[$index]['is_ready'] = $decorateReady ? $isReady : $isReady;
            $steps[$index]['is_blocked'] = $decorateReady ? $isBlocked : $isBlocked;
            $steps[$index]['is_current'] = $workflow->currentStepId !== null && $workflow->currentStepId === $step['id'];
            $steps[$index]['display_status'] = $this->deriveDisplayStatus($steps[$index]);
            $steps[$index]['validation_required'] = !empty($steps[$index]['requires_validation']) || !empty($steps[$index]['key_step']);
        }

        return $steps;
    }

    private function deriveDisplayStatus(array $step): string
    {
        if (!empty($step['is_current']) && ($step['status'] ?? null) === ProductionWorkflow::STEP_STATUS_PENDING) {
            return ProductionWorkflow::STEP_STATUS_IN_PROGRESS;
        }

        if (!empty($step['is_blocked']) && ($step['status'] ?? null) === ProductionWorkflow::STEP_STATUS_PENDING) {
            return 'blocked';
        }

        return (string) ($step['status'] ?? ProductionWorkflow::STEP_STATUS_PENDING);
    }

    private function firstStepWithStatus(array $steps, string $status): ?array
    {
        foreach ($steps as $step) {
            if (($step['status'] ?? null) === $status) {
                return $step;
            }
        }

        return null;
    }

    private function hasNeedsCorrectionStep(array $steps): bool
    {
        foreach ($steps as $step) {
            if (($step['status'] ?? null) === ProductionWorkflow::STEP_STATUS_NEEDS_CORRECTION) {
                return true;
            }
        }

        return false;
    }

    private function firstAvailableStepId(array $steps): ?string
    {
        $fallback = null;

        foreach ($steps as $step) {
            if (($step['status'] ?? null) === ProductionWorkflow::STEP_STATUS_VALIDATED) {
                continue;
            }

            if (!empty($step['is_ready'])) {
                return $step['id'];
            }

            if ($fallback === null) {
                $fallback = $step['id'];
            }
        }

        return $fallback;
    }

    private function dependenciesAreSatisfied(array $dependsOn, array $validatedIds): bool
    {
        if ($dependsOn === []) {
            return true;
        }

        foreach ($dependsOn as $dependencyId) {
            if (!in_array($dependencyId, $validatedIds, true)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeWorkflowType(mixed $value): string
    {
        $type = strtolower((string) ($this->stringOrNull($value) ?? ProductionWorkflow::TYPE_PRODUCTION_PLAN));
        $type = str_replace([' ', '-'], '_', $type);

        return match ($type) {
            'sample', 'sample_plan', 'plan_echantillon', 'echantillon' => ProductionWorkflow::TYPE_SAMPLE_PLAN,
            default => ProductionWorkflow::TYPE_PRODUCTION_PLAN,
        };
    }

    private function normalizeStyles(mixed $styles, array $existingStyles = []): array
    {
        $incoming = is_array($styles) ? $styles : [];
        $existingById = [];
        foreach ($existingStyles as $style) {
            if (is_array($style) && isset($style['id'])) {
                $existingById[$style['id']] = $style;
            }
        }

        $normalized = [];
        foreach ($incoming as $index => $style) {
            if (!is_array($style)) {
                continue;
            }

            $styleId = $this->stringOrNull($style['id'] ?? null) ?? $this->uuidV4();
            $merged = array_merge($existingById[$styleId] ?? [], $style);

            $normalized[] = [
                'id' => $styleId,
                'client_name' => $this->stringOrNull($merged['client_name'] ?? $merged['client'] ?? null),
                'style_name' => $this->stringOrNull($merged['style_name'] ?? $merged['style'] ?? $merged['name'] ?? null) ?? 'Style ' . ($index + 1),
                'approval_date' => $this->dateOrNull($merged['approval_date'] ?? $merged['approv_date'] ?? $merged['date'] ?? null),
                'size' => $this->stringOrNull($merged['size'] ?? null),
                'sizes' => $this->normalizeStringArray($merged['sizes'] ?? []),
                'quantity' => $this->numberOrNull($merged['quantity'] ?? $merged['qty'] ?? null),
                'quantity_order' => $this->numberOrNull($merged['quantity_order'] ?? $merged['qty_order'] ?? null),
                'pattern_status' => $this->statusOrNull($merged['pattern_status'] ?? $merged['pattern'] ?? $merged['patern'] ?? $merged['ptrn'] ?? null),
                'fabric_status' => $this->statusOrNull($merged['fabric_status'] ?? $merged['fabric'] ?? $merged['fbrc'] ?? null),
                'thread_status' => $this->statusOrNull($merged['thread_status'] ?? $merged['thread'] ?? null),
                'care_status' => $this->statusOrNull($merged['care_status'] ?? $merged['care'] ?? null),
                'size_model_status' => $this->statusOrNull($merged['size_model_status'] ?? $merged['size_model'] ?? null),
                'print_embroidery_status' => $this->statusOrNull($merged['print_embroidery_status'] ?? $merged['print_embrod'] ?? null),
                'pp_meeting_status' => $this->statusOrNull($merged['pp_meeting_status'] ?? $merged['pp_meeting'] ?? null),
                'start_cutting_status' => $this->statusOrNull($merged['start_cutting_status'] ?? $merged['start_cutting'] ?? null),
                'start_line_date' => $this->dateOrNull($merged['start_line_date'] ?? $merged['start_line'] ?? null),
                'smv' => $this->numberOrNull($merged['smv'] ?? null),
                'operator_total' => $this->numberOrNull($merged['operator_total'] ?? null),
                'target_100_percent' => $this->numberOrNull($merged['target_100_percent'] ?? $merged['target_100'] ?? null),
                'delivery_date' => $this->dateOrNull($merged['delivery_date'] ?? $merged['d_date'] ?? null),
                'launch_date' => $this->dateOrNull($merged['launch_date'] ?? $merged['launch'] ?? null),
                'hints' => $this->stringOrNull($merged['hints'] ?? null),
                'notes' => $this->stringOrNull($merged['notes'] ?? null),
                'position' => $index + 1,
            ];
        }

        return $normalized;
    }

    private function normalizeMeasurements(mixed $measurements): ?array
    {
        if (!is_array($measurements)) {
            return null;
        }

        return [
            'reference' => $this->stringOrNull($measurements['reference'] ?? null),
            'movement' => $this->stringOrNull($measurements['movement'] ?? null),
            'category' => $this->stringOrNull($measurements['category'] ?? null),
            'fit' => $this->stringOrNull($measurements['fit'] ?? null),
            'garment_type' => $this->stringOrNull($measurements['garment_type'] ?? null),
            'sizes' => $this->normalizeStringArray($measurements['sizes'] ?? []),
            'rows' => $this->normalizeMeasurementRows($measurements['rows'] ?? []),
            'notes' => $this->stringOrNull($measurements['notes'] ?? null),
        ];
    }

    private function normalizeMeasurementRows(mixed $rows): array
    {
        $incoming = is_array($rows) ? $rows : [];
        $normalized = [];

        foreach ($incoming as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = [
                'code' => $this->stringOrNull($row['code'] ?? null),
                'label_fr' => $this->stringOrNull($row['label_fr'] ?? $row['label'] ?? null) ?? 'Mesure ' . ($index + 1),
                'label_en' => $this->stringOrNull($row['label_en'] ?? null),
                'values' => $this->normalizeMeasurementValues($row['values'] ?? []),
                'notes' => $this->stringOrNull($row['notes'] ?? null),
                'position' => $index + 1,
            ];
        }

        return $normalized;
    }

    private function normalizeMeasurementValues(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $size => $value) {
            $sizeLabel = $this->stringOrNull($size);
            if ($sizeLabel === null) {
                continue;
            }

            $normalized[$sizeLabel] = $this->numberOrNull($value) ?? $this->stringOrNull($value);
        }

        return $normalized;
    }

    private function normalizeSteps(mixed $steps, array $existingSteps = []): array
    {
        $incoming = is_array($steps) ? $steps : [];
        $existingById = [];
        foreach ($existingSteps as $step) {
            if (is_array($step) && isset($step['id'])) {
                $existingById[$step['id']] = $step;
            }
        }

        $normalized = [];
        foreach ($incoming as $index => $step) {
            if (!is_array($step)) {
                continue;
            }

            $stepId = $this->stringOrNull($step['id'] ?? null) ?? $this->uuidV4();
            $merged = array_merge($existingById[$stepId] ?? [], $step);
            $status = $this->normalizeStepStatus($merged['status'] ?? ProductionWorkflow::STEP_STATUS_PENDING);
            $isKeyStep = $this->normalizeBoolean($merged['key_step'] ?? false);
            $requiresValidation = array_key_exists('requires_validation', $merged)
                ? $this->normalizeBoolean($merged['requires_validation'])
                : $isKeyStep;

            $normalized[] = [
                'id' => $stepId,
                'title' => $this->stringOrNull($merged['title'] ?? null) ?? 'Etape ' . ($index + 1),
                'objective' => $this->stringOrNull($merged['objective'] ?? null) ?? '',
                'roles' => $this->normalizeStringArray($merged['roles'] ?? []),
                'depends_on' => $this->normalizeStringArray($merged['depends_on'] ?? []),
                'key_step' => $isKeyStep,
                'requires_validation' => $requiresValidation,
                'validation_roles' => $this->normalizeValidationRoles($merged['validation_roles'] ?? ($requiresValidation ? ['worker', 'admin'] : [])),
                'notes' => $this->stringOrNull($merged['notes'] ?? null),
                'status' => $status,
                'position' => $index + 1,
                'validated_at' => $this->stringOrNull($merged['validated_at'] ?? null),
                'validated_by' => $this->stringOrNull($merged['validated_by'] ?? null),
                'rejected_at' => $this->stringOrNull($merged['rejected_at'] ?? null),
                'rejected_by' => $this->stringOrNull($merged['rejected_by'] ?? null),
                'rejection_reason' => $this->stringOrNull($merged['rejection_reason'] ?? null),
                'correction_notes' => $this->stringOrNull($merged['correction_notes'] ?? null),
            ];
        }

        $validIds = array_map(static fn(array $step) => $step['id'], $normalized);
        foreach ($normalized as $index => $step) {
            $normalized[$index]['depends_on'] = array_values(array_unique(array_values(array_filter(
                $step['depends_on'],
                static fn(string $dependencyId) => in_array($dependencyId, $validIds, true) && $dependencyId !== $step['id']
            ))));
        }

        return $normalized;
    }

    private function syncStepDefaults(array $step, int $position): array
    {
        $step['position'] = $position;
        $step['roles'] = $this->normalizeStringArray($step['roles'] ?? []);
        $step['depends_on'] = $this->normalizeStringArray($step['depends_on'] ?? []);
        $step['validation_roles'] = $this->normalizeValidationRoles($step['validation_roles'] ?? []);
        $step['key_step'] = $this->normalizeBoolean($step['key_step'] ?? false);
        $step['requires_validation'] = array_key_exists('requires_validation', $step)
            ? $this->normalizeBoolean($step['requires_validation'])
            : $step['key_step'];
        $step['status'] = $this->normalizeStepStatus($step['status'] ?? ProductionWorkflow::STEP_STATUS_PENDING);

        return $step;
    }

    private function normalizeStepStatus(mixed $status): string
    {
        $allowed = [
            ProductionWorkflow::STEP_STATUS_PENDING,
            ProductionWorkflow::STEP_STATUS_IN_PROGRESS,
            ProductionWorkflow::STEP_STATUS_AWAITING_VALIDATION,
            ProductionWorkflow::STEP_STATUS_VALIDATED,
            ProductionWorkflow::STEP_STATUS_NEEDS_CORRECTION,
        ];

        $status = $this->stringOrNull($status) ?? ProductionWorkflow::STEP_STATUS_PENDING;

        return in_array($status, $allowed, true) ? $status : ProductionWorkflow::STEP_STATUS_PENDING;
    }

    private function normalizeStringArray(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\n,;]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $values = [];
        foreach ($value as $item) {
            $item = $this->stringOrNull($item);
            if ($item !== null && $item !== '') {
                $values[] = $item;
            }
        }

        return array_values(array_unique($values));
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function statusOrNull(mixed $value): ?string
    {
        $status = strtolower((string) ($this->stringOrNull($value) ?? ''));
        if ($status === '') {
            return null;
        }

        $status = str_replace([' ', '-'], '_', $status);

        return match ($status) {
            'ok', 'yes', 'true', '1', 'done', 'validated', 'valide' => 'ok',
            'no', 'false', '0', 'missing', 'blocked', 'non' => 'no',
            'pending', 'todo', 'a_faire' => 'pending',
            default => $status,
        };
    }

    private function dateOrNull(mixed $value): ?string
    {
        $date = $this->stringOrNull($value);
        if ($date === null) {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd/m/y', 'd-m-Y', 'd-m-y'];
        foreach ($formats as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!' . $format, $date);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed->format('Y-m-d');
            }
        }

        $timestamp = strtotime($date);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function numberOrNull(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $number = $this->stringOrNull($value);
        if ($number === null) {
            return null;
        }

        $normalized = str_replace(',', '.', $number);

        if (!is_numeric($normalized)) {
            return null;
        }

        $numeric = (float) $normalized;

        return floor($numeric) === $numeric ? (int) $numeric : $numeric;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed !== '' ? $trimmed : null;
        }

        if (is_scalar($value)) {
            $string = trim((string) $value);
            return $string !== '' ? $string : null;
        }

        return null;
    }

    private function buildHistoryEntry(string $action, ?string $stepId, array $context = [], ?array $actor = null): array
    {
        return [
            'id' => $this->uuidV4(),
            'action' => $action,
            'step_id' => $stepId,
            'context' => $context,
            'actor' => $this->actorSummary($actor),
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function actorSummary(?array $actor): ?array
    {
        if (!$actor) {
            return null;
        }

        return [
            'id' => $actor['id'] ?? null,
            'role' => $actor['role'] ?? null,
            'name' => trim(($actor['first_name'] ?? '') . ' ' . ($actor['last_name'] ?? '')) ?: null,
            'email' => $actor['email'] ?? null,
        ];
    }

    private function actorLabel(?array $actor): ?string
    {
        $summary = $this->actorSummary($actor);
        if (!$summary) {
            return null;
        }

        return $summary['name'] ?: $summary['role'] ?: $summary['email'] ?: $summary['id'];
    }

    private function actorRole(?array $actor): ?string
    {
        return $this->normalizeRoleAlias($actor['role'] ?? null);
    }

    private function canValidateStep(array $step, ?array $actor): bool
    {
        $actorRole = $this->actorRole($actor);
        if ($actorRole === 'admin') {
            return true;
        }

        $allowedRoles = $this->normalizeValidationRoles($step['validation_roles'] ?? []);
        if ($allowedRoles === []) {
            return $actorRole === 'worker';
        }

        return $actorRole !== null && in_array($actorRole, $allowedRoles, true);
    }

    private function normalizeValidationRoles(mixed $value): array
    {
        $roles = $this->normalizeStringArray($value);
        $normalized = [];

        foreach ($roles as $role) {
            $alias = $this->normalizeRoleAlias($role);
            if ($alias !== null) {
                $normalized[] = $alias;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeRoleAlias(mixed $role): ?string
    {
        $normalized = $this->stringOrNull($role);
        if ($normalized === null) {
            return null;
        }

        return match (strtolower($normalized)) {
            'client' => 'user',
            'customer' => 'user',
            default => strtolower($normalized),
        };
    }

    private function findStepIndex(array $steps, string $stepId): ?int
    {
        foreach ($steps as $index => $step) {
            if (($step['id'] ?? null) === $stepId) {
                return $index;
            }
        }

        return null;
    }

    private function hasDependencyCycle(array $steps): bool
    {
        $graph = [];
        foreach ($steps as $step) {
            $graph[$step['id']] = $this->normalizeStringArray($step['depends_on'] ?? []);
        }

        $visited = [];
        $stack = [];

        $visit = function (string $node) use (&$visit, &$graph, &$visited, &$stack): bool {
            if (($stack[$node] ?? false) === true) {
                return true;
            }

            if (($visited[$node] ?? false) === true) {
                return false;
            }

            $visited[$node] = true;
            $stack[$node] = true;

            foreach ($graph[$node] ?? [] as $dependency) {
                if (isset($graph[$dependency]) && $visit($dependency)) {
                    return true;
                }
            }

            $stack[$node] = false;

            return false;
        };

        foreach (array_keys($graph) as $node) {
            if ($visit($node)) {
                return true;
            }
        }

        return false;
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }
}
