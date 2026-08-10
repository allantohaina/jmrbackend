<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'birth_date',
        'country',
        'address',
        'role',
        'is_active',
        'is_privileged',
        'failed_login_count',
        'locked_until',
        'last_failed_login_at',
        'last_login_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'first_name' => 'required|min_length[2]|max_length[100]',
        'last_name' => 'required|min_length[2]|max_length[100]',
        'phone' => 'permit_empty|max_length[20]|is_unique[users.phone,id,{id}]',
        'birth_date' => 'permit_empty|valid_date',
        'country' => 'permit_empty|max_length[2]',
        'address' => 'permit_empty|max_length[255]',
        'role' => 'permit_empty|in_list[admin,user,worker]',
    ];

    protected $validationMessages = [
        'email' => [
            'required' => 'Users.validation.email.required',
            'valid_email' => 'Users.validation.email.valid_email',
            'is_unique' => 'Users.validation.email.is_unique'
        ],
        'password' => [
            'required' => 'Users.validation.password.required',
            'min_length' => 'Users.validation.password.min_length'
        ],
        'first_name' => [
            'required' => 'Users.validation.first_name.required',
            'min_length' => 'Users.validation.first_name.min_length'
        ],
        'last_name' => [
            'required' => 'Users.validation.last_name.required',
            'min_length' => 'Users.validation.last_name.min_length'
        ],
        'phone' => [
            'is_unique' => 'Users.validation.phone.is_unique'
        ]
    ];

    protected $beforeInsert = ['hashPassword', 'generateUUID'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash password before insert/update
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            unset($data['data']['password']);
        }
        return $data;
    }

    /**
     * Generate UUID for new users
     */
    protected function generateUUID(array $data): array
    {
        if (!isset($data['data']['id'])) {
            $data['data']['id'] = $this->uuidV4();
        }
        return $data;
    }

    /**
     * Generate a UUID v4 string.
     */
    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    /**
     * Verify user credentials
     */
    public function verifyCredentials(string $email, string $password): ?array
    {
        $user = $this->where('email', $email)
                     ->where('is_active', true)
                     ->first();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Remove password hash from returned data
        unset($user['password_hash']);
        return $user;
    }

    public function getUserForLogin(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function recordLoginSuccess(string $userId): void
    {
        $this->update($userId, [
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recordLoginFailure(string $userId, int $failedCount, ?string $lockedUntil): void
    {
        $this->update($userId, [
            'failed_login_count' => $failedCount,
            'locked_until' => $lockedUntil,
            'last_failed_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get user by ID without password
     */
    public function getUserById(string $id): ?array
    {
        $user = $this->find($id);
        if ($user) {
            unset($user['password_hash']);
            unset($user['failed_login_count'], $user['locked_until'], $user['last_failed_login_at'], $user['last_login_at']);
        }
        return $user;
    }

    /**
     * Get all users without passwords
     */
    public function getAllUsers(): array
    {
        $this->select('id, email, first_name, last_name, phone, role, is_active, is_privileged, country, address, birth_date, last_login_at, created_at, updated_at, deleted_at');
        $users = $this->findAll();
        return array_map(function ($user) {
            unset($user['password_hash']);
            return $user;
        }, $users);
    }

    /**
     * Update user profile
     */
    public function updateProfile(string $id, array $data): bool
    {
        // Remove fields that shouldn't be updated via profile
        unset($data['id'], $data['role'], $data['created_at'], $data['updated_at']);
        
        return $this->update($id, $data);
    }


    /**
     * Get clients with cumulative revenue from delivered commandes
     */
    public function getClientsWithRevenue(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users u');
        $builder->select('u.id, u.email, u.first_name, u.last_name, u.phone, u.role, u.is_active, u.is_privileged, u.created_at');
        $builder->select('COALESCE(SUM(CASE WHEN c.statut_production = \'Livrée\' THEN c.total ELSE 0 END), 0) as cumulative_revenue');
        $builder->join('commandes c', 'c.client_id = u.id', 'left');
        $builder->where('u.role', 'user');
        $builder->where('u.deleted_at', null);
        $builder->groupBy('u.id');
        $builder->orderBy('cumulative_revenue', 'DESC');
        return $builder->get()->getResultArray();
    }

    /**
     * Strict validation rules for updates (password optional but strict if provided)
     */
    public function getStrictUpdateRules(): array
    {
        return [
            'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
            'password' => 'permit_empty|min_length[8]',
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'phone' => 'permit_empty|max_length[20]|is_unique[users.phone,id,{id}]',
            'role' => 'permit_empty|in_list[admin,user,worker]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ];
    }

}
