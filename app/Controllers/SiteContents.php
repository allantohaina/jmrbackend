<?php

namespace App\Controllers;

use App\Models\SiteContentModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

// Contenus éditables du site public (textes + images) : lecture publique,
// écriture réservée au backoffice (filtres auth + admin sur la route PUT).
class SiteContents extends ResourceController
{
    protected $format = 'json';

    // GET /api/site-contents?locale=fr -> { "cle": "valeur", ... }
    public function index(): ResponseInterface
    {
        $locale = $this->request->getGet('locale') === 'en' ? 'en' : 'fr';

        $model = new SiteContentModel();
        $rows = $model->where('locale', $locale)->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['content_key']] = $row['value'];
        }

        return $this->respond($map);
    }

    // PUT /api/site-contents { key, value, locale?, type? } (auth + admin)
    public function save(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
            $key = trim((string) ($input['key'] ?? ''));
            $value = (string) ($input['value'] ?? '');
            $locale = ($input['locale'] ?? 'fr') === 'en' ? 'en' : 'fr';
            $type = ($input['type'] ?? 'text') === 'image' ? 'image' : 'text';

            if ($key === '' || strlen($key) > 191 || $value === '') {
                return $this->failValidationErrors('Clé ou valeur manquante.');
            }

            $model = new SiteContentModel();
            $existing = $model->where('content_key', $key)->where('locale', $locale)->first();

            if ($existing) {
                $model->update($existing['id'], ['value' => $value, 'type' => $type]);
                return $this->respond(['message' => 'Contenu mis à jour.', 'key' => $key]);
            }

            $model->insert([
                'content_key' => $key,
                'locale'      => $locale,
                'type'        => $type,
                'value'       => $value,
            ]);

            return $this->respondCreated(['message' => 'Contenu créé.', 'key' => $key]);
        } catch (Throwable $e) {
            log_message('error', 'SiteContents::save: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur.');
        }
    }
}
