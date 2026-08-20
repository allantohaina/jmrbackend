<?php

namespace App\Controllers;

use App\Application\Paiement\LienPaiementService;
use App\Application\Recu\RecuService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class CommandesExtras extends ResourceController
{
    protected $format = 'json';

    public function recuData($id = null): ResponseInterface
    {
        try {
            $result = (new RecuService())->getData((string)$id);
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            return $this->respond($result->getPayload());
        } catch (Throwable $e) {
            log_message('error', 'CommandesExtras recuData error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function recuPdf($id = null): ResponseInterface
    {
        try {
            $result = (new RecuService())->generatePdf((string)$id);
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            $payload = $result->getPayload();
            $response = $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $payload['filename'] . '"');
            $response->setBody($payload['pdf']);
            return $response;
        } catch (Throwable $e) {
            log_message('error', 'CommandesExtras recuPdf error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function lienPaiement($id = null): ResponseInterface
    {
        try {
            $actorId = $this->request->user['id'] ?? null;
            $input = $this->getInputData();
            $result = (new LienPaiementService())->generate((string)$id, $input, $actorId);
            if ($result->isSuccess()) return $this->respondCreated($result->getPayload());
            return $this->fail($result->getPayload(), $result->getStatus());
        } catch (Throwable $e) {
            log_message('error', 'CommandesExtras lienPaiement error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }

    public function qrData($id = null): ResponseInterface
    {
        try {
            $commande = (new \App\Models\CommandeModel())->find((string)$id);
            if (!$commande) return $this->failNotFound('Commande introuvable.');
            $frontendUrl = rtrim((string) getenv('FRONTEND_URL'), '/');
            $url = ($frontendUrl !== '' ? $frontendUrl : base_url()) . '/suivi-commande?numero=' . urlencode((string)$commande['numero']);
            return $this->respond(['data' => ['url' => $url, 'numero' => $commande['numero']]]);
        } catch (Throwable $e) {
            log_message('error', 'CommandesExtras qrData error: ' . $e->getMessage());
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