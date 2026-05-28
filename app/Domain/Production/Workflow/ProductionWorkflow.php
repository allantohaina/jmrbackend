<?php

namespace App\Domain\Production\Workflow;

use App\Traits\UuidTrait;

class ProductionWorkflow
{
    use UuidTrait;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_NEEDS_CORRECTION = 'needs_correction';
    public const STATUS_COMPLETED = 'completed';

    public const TYPE_SAMPLE_PLAN = 'sample_plan';
    public const TYPE_PRODUCTION_PLAN = 'production_plan';

    public const STEP_STATUS_PENDING = 'pending';
    public const STEP_STATUS_IN_PROGRESS = 'in_progress';
    public const STEP_STATUS_AWAITING_VALIDATION = 'awaiting_validation';
    public const STEP_STATUS_VALIDATED = 'validated';
    public const STEP_STATUS_NEEDS_CORRECTION = 'needs_correction';

    public function __construct(
        public readonly string $id,
        public ?string $projectId,
        public string $name,
        public string $workflowType,
        public ?string $clientName,
        public ?string $approvalDate,
        public ?string $deliveryDate,
        public ?string $launchDate,
        public string $status,
        public ?string $currentStepId,
        public ?string $lastValidatedStepId,
        public array $styles,
        public ?array $measurements,
        public ?string $productionNotes,
        public array $steps,
        public array $history,
        public ?array $rollbackContext,
        public readonly string $createdAt,
        public string $updatedAt
    ) {
    }

    public static function create(
        ?string $name = null,
        ?string $projectId = null,
        array $steps = [],
        string $workflowType = self::TYPE_PRODUCTION_PLAN,
        ?string $clientName = null,
        ?string $approvalDate = null,
        ?string $deliveryDate = null,
        ?string $launchDate = null,
        array $styles = [],
        ?array $measurements = null,
        ?string $productionNotes = null
    ): self {
        $now = date('Y-m-d H:i:s');

        return new self(
            self::uuidV4(),
            $projectId,
            self::normalizeName($name),
            $workflowType,
            $clientName,
            $approvalDate,
            $deliveryDate,
            $launchDate,
            self::STATUS_DRAFT,
            null,
            null,
            $styles,
            $measurements,
            $productionNotes,
            $steps,
            [],
            null,
            $now,
            $now
        );
    }

    private static function normalizeName(?string $name): string
    {
        $trimmed = trim((string) $name);

        return $trimmed !== '' ? $trimmed : 'Processus de production';
    }
}
