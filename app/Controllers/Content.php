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
            $cached = cache('site_content');
            if ($cached !== null) {
                $this->response->setHeader('Cache-Control', 'no-store, max-age=0');
                return $this->respond($cached);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('site_content');
            $rows = $builder->select('key, value')->get()->getResultArray();
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }

            cache('site_content', $result, 300);
            $this->response->setHeader('Cache-Control', 'no-store, max-age=0');

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

            cache()->delete('site_content');

            return $this->respond(['key' => $key, 'value' => $value]);
        } catch (Throwable $e) {
            log_message('error', 'Content update error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne');
        }
    }

    /** Publishes a complete content snapshot in one transaction. */
    public function publish(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true);
            $content = $input['content'] ?? null;
            if (!is_array($content)) return $this->failValidationErrors('Contenu invalide');

            $resetKeys = $input['reset_keys'] ?? [];
            if (!is_array($resetKeys)) return $this->failValidationErrors('Clés à réinitialiser invalides');
            foreach ($content as $key => $value) {
                if (!is_string($key) || !is_string($value) || strlen($key) > 100) return $this->failValidationErrors('Une valeur de contenu est invalide');
            }

            $db = \Config\Database::connect();
            $db->transStart();
            $now = date('Y-m-d H:i:s');
            foreach ($content as $key => $value) {
                $exists = $db->table('site_content')->where('key', $key)->countAllResults() > 0;
                $exists
                    ? $db->table('site_content')->where('key', $key)->update(['value' => $value, 'updated_at' => $now])
                    : $db->table('site_content')->insert(['key' => $key, 'value' => $value, 'updated_at' => $now]);
            }
            foreach ($resetKeys as $key) if (is_string($key) && strlen($key) <= 100) $db->table('site_content')->where('key', $key)->delete();

            $snapshot = [];
            foreach ($db->table('site_content')->select('key, value')->get()->getResultArray() as $row) $snapshot[$row['key']] = $row['value'];
            $user = $this->request->user ?? [];
            $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? null);
            $db->table('site_content_versions')->insert([
                'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'author_id' => $user['id'] ?? null,
                'author_name' => $name,
                'created_at' => $now,
            ]);
            $db->transComplete();
            if (!$db->transStatus()) return $this->failServerError('Publication impossible');
            cache()->delete('site_content');
            return $this->respond(['content' => $snapshot, 'message' => 'Publication effectuée.']);
        } catch (Throwable $e) {
            log_message('error', 'Content publish error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne');
        }
    }

    public function history(): ResponseInterface
    {
        try {
            $rows = \Config\Database::connect()->table('site_content_versions')->select('id, author_name, created_at')->orderBy('id', 'DESC')->limit(30)->get()->getResultArray();
            return $this->respond($rows);
        } catch (Throwable $e) { return $this->failServerError('Erreur interne'); }
    }

    public function restore($id = null): ResponseInterface
    {
        try {
            $db = \Config\Database::connect();
            $version = $db->table('site_content_versions')->where('id', $id)->get()->getRowArray();
            $snapshot = $version ? json_decode($version['snapshot'], true) : null;
            if (!is_array($snapshot)) return $this->failNotFound('Version introuvable');
            $current = [];
            foreach ($db->table('site_content')->select('key')->get()->getResultArray() as $row) $current[] = $row['key'];
            $this->request->setBody(json_encode(['content' => $snapshot, 'reset_keys' => array_values(array_diff($current, array_keys($snapshot)))]));
            return $this->publish();
        } catch (Throwable $e) { return $this->failServerError('Erreur interne'); }
    }

    /**
     * Removes a site-content override. Public components then fall back to their
     * bundled default value; uploaded files are intentionally never deleted here.
     */
    public function remove($key = null): ResponseInterface
    {
        try {
            if (!$key) return $this->failValidationErrors('ClÃ© requise');

            $db = \Config\Database::connect();
            $db->table('site_content')->where('key', $key)->delete();
            cache()->delete('site_content');

            return $this->respondDeleted(['key' => $key]);
        } catch (Throwable $e) {
            log_message('error', 'Content remove error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne');
        }
    }
}
