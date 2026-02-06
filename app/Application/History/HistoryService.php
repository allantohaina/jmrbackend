<?php

namespace App\Application\History;

use App\Application\Shared\Result;
use CodeIgniter\HTTP\IncomingRequest;

class HistoryService
{
    public function audit(IncomingRequest $request): Result
    {
        return Result::ok($this->paginate('audit_logs', $request));
    }

    public function tokens(IncomingRequest $request): Result
    {
        return Result::ok($this->paginate('token_history', $request));
    }

    public function projects(IncomingRequest $request): Result
    {
        return Result::ok($this->paginate('order_project_history', $request));
    }

    private function paginate(string $table, IncomingRequest $request): array
    {
        $limit = (int) ($request->getGet('limit') ?? 50);
        $limit = max(1, min(200, $limit));
        $offset = (int) ($request->getGet('offset') ?? 0);
        $offset = max(0, $offset);

        $builder = db_connect()->table($table)->orderBy('created_at', 'DESC');

        $action = $request->getGet('action');
        if ($action) {
            $builder->where('action', $action);
        }

        $entityType = $request->getGet('entity_type');
        if ($entityType && $table === 'audit_logs') {
            $builder->where('entity_type', $entityType);
        }

        $rows = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'data' => $rows,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}

