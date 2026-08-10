<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\UuidTrait;

class NotificationModel extends Model
{
    use UuidTrait;

    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'recipient_user_id', 'actor_user_id', 'entity_type', 'entity_id', 'event',
        'type', 'title', 'message', 'action_url', 'read_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data): array
    {
        if (empty($data['data']['id'])) $data['data']['id'] = static::uuidV4();
        return $data;
    }
}
