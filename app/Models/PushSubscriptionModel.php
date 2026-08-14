<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\UuidTrait;

class PushSubscriptionModel extends Model
{
    use UuidTrait;

    protected $table = 'push_subscriptions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id', 'endpoint', 'keys_p256dh', 'keys_auth',
    ];
    protected $useTimestamps = false;
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = static::uuidV4();
        return $data;
    }
}