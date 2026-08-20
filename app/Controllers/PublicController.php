<?php

namespace App\Controllers;

use App\Application\Paiement\LienPaiementService;
use App\Application\Suivi\SuiviCommandeService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class PublicController extends ResourceController
{
    protected $format = 'json';

    public function suiviCommande(): ResponseInterface
    {
        try {
            $input = $this->getInputData();
            $result = (new SuiviCommandeService())->lookup(
                (string)($input['numero'] ?? ''),
                (string)($input['email'] ?? '')
            );
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'Public suiviCommande error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function lienInfo($token = null): ResponseInterface
    {
        try {
            $result = (new LienPaiementService())->getByToken((string)$token);
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'Public lienInfo error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function lienPayer($token = null): ResponseInterface
    {
        try {
            $result = (new LienPaiementService())->pay((string)$token);
            if ($result->isSuccess()) return $this->respondCreated($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'Public lienPayer error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    private function getInputData(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (Throwable) {
        }
        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) return $raw;
        $post = $this->request->getPost();
        return is_array($post) ? $post : [];
    }
}