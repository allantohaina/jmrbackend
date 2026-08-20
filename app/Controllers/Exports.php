<?php

namespace App\Controllers;

use App\Application\Exports\ExportService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Exports extends ResourceController
{
    protected $format = 'json';

    public function devis(): ResponseInterface
    {
        return $this->csv('devisCsv', 'devis');
    }

    public function commandes(): ResponseInterface
    {
        return $this->csv('commandesCsv', 'commandes');
    }

    public function paiements(): ResponseInterface
    {
        return $this->csv('paiementsCsv', 'paiements');
    }

    private function csv(string $method, string $label): ResponseInterface
    {
        try {
            $result = (new ExportService())->{$method}();
            if (!$result->isSuccess()) return $this->fail($result->getPayload(), $result->getStatus());
            $payload = $result->getPayload();
            $response = $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $payload['filename'] . '"')
                ->setHeader('Content-Transfer-Encoding', 'binary');
            $bom = "\xEF\xBB\xBF";
            $response->setBody($bom . $payload['csv']);
            return $response;
        } catch (Throwable $e) {
            log_message('error', 'Exports ' . $label . ' error: ' . $e->getMessage());
            return $this->failServerError('Erreur interne du serveur');
        }
    }
}