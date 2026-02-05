<?php

namespace App\Libraries;

use App\Models\AuditLogModel;

class AuditLogger
{
    public static function log(array $payload): void
    {
        $model = new AuditLogModel();
        $model->insert($payload);
    }
}
