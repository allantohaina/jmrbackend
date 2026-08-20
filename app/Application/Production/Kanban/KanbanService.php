<?php

namespace App\Application\Production\Kanban;

use App\Application\Shared\Result;
use App\Models\ProductionWorkflowModel;

class KanbanService
{
    public const STATUSES = [
        'draft' => 'Brouillon',
        'active' => 'En cours',
        'needs_correction' => 'À corriger',
        'completed' => 'Terminé',
    ];

    public function board(): Result
    {
        $rows = (new ProductionWorkflowModel())->orderBy('updated_at', 'DESC')->findAll();
        $columns = [];
        foreach (array_keys(self::STATUSES) as $status) {
            $columns[$status] = [];
        }
        foreach ($rows as $r) {
            $status = (string)($r['status'] ?? 'draft');
            if (!isset($columns[$status])) {
                $columns[$status] = [];
            }
            $steps = json_decode((string)($r['steps'] ?? '[]'), true);
            $currentStep = null;
            if (is_array($steps)) {
                foreach ($steps as $step) {
                    if (!empty($step['is_current'])) {
                        $currentStep = $step['title'] ?? $step['name'] ?? null;
                        break;
                    }
                }
            }
            $columns[$status][] = [
                'id' => $r['id'],
                'name' => $r['name'] ?? '',
                'client_name' => $r['client_name'] ?? '',
                'workflow_type' => $r['workflow_type'] ?? 'production_plan',
                'delivery_date' => $r['delivery_date'] ?? null,
                'launch_date' => $r['launch_date'] ?? null,
                'current_step' => $currentStep,
                'updated_at' => $r['updated_at'] ?? null,
            ];
        }
        return Result::ok([
            'data' => $columns,
            'counts' => array_map('count', $columns),
            'status_labels' => self::STATUSES,
        ]);
    }
}