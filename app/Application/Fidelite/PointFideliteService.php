<?php

namespace App\Application\Fidelite;

use App\Application\Shared\Result;
use App\Models\PointFideliteModel;
use App\Models\UserModel;

class PointFideliteService
{
    public function award(string $userId, int $points, string $motif, ?string $referenceType = null, ?string $referenceId = null): bool
    {
        if ($points <= 0) return false;
        $model = new PointFideliteModel();
        return $model->insert([
            'user_id' => $userId,
            'points' => $points,
            'motif' => $motif,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function balance(string $userId): int
    {
        return (new PointFideliteModel())->balance($userId);
    }

    public function history(string $userId): Result
    {
        $rows = (new PointFideliteModel())
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
        return Result::ok([
            'data' => $rows,
            'solde' => (new PointFideliteModel())->balance($userId),
        ]);
    }
}