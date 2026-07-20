<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Content extends ResourceController
{
    protected $format = 'json';

    public function index(): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('site_content');
            $rows = $builder->select('key, value')->get()->getResultArray();
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }
            return $this->respond($result);
        } catch (Throwable $e) {
            log_message('error', 'Content index error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne');
        }
    }

    public function update($key = null): ResponseInterface
    {
        try {
            if (!$key) return $this->failValidationErrors('Clé requise');

            $input = $this->request->getJSON(true);
            $value = $input['value'] ?? '';

            $db = \Config\Database::connect();
            $builder = $db->table('site_content');

            $exists = $builder->where('key', $key)->countAllResults() > 0;

            if ($exists) {
                $builder->where('key', $key)->update(['value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $builder->insert(['key' => $key, 'value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
            }

            return $this->respond(['key' => $key, 'value' => $value]);
        } catch (Throwable $e) {
            log_message('error', 'Content update error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne');
        }
    }
}
