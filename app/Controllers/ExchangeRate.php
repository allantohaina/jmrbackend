<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class ExchangeRate extends BaseController
{
    use ResponseTrait;
    private float $cacheTTL = 3600;
    private string $cacheFile = WRITEPATH . 'cache/exchange_rates.json';

    public function index(): ResponseInterface
    {
        $cached = $this->readCache();
        if ($cached !== null) {
            return $this->respond($cached);
        }

        $client   = \Config\Services::curlrequest();
        $base     = $this->request->getGet('base') ?: 'USD';
        $symbols  = $this->request->getGet('symbols') ?: 'MGA,EUR';

        try {
            $response = $client->get("https://api.frankfurter.dev/v1/latest?base={$base}&symbols={$symbols}", [
                'timeout' => 10,
                'follow_redirects' => true,
                'max_redirects' => 5,
            ]);

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200 || empty($body['rates'])) {
                return $this->failServerError('Unable to fetch exchange rates');
            }

            $data = [
                'base'      => $base,
                'rates'     => $body['rates'],
                'date'      => $body['date'] ?? date('Y-m-d'),
                'fetched_at'=> date('c'),
            ];

            $this->writeCache($data);

            return $this->respond($data);
        } catch (\Exception $e) {
            log_message('error', 'ExchangeRate fetch failed: ' . $e->getMessage());
            return $this->failServerError('Exchange rate service unavailable');
        }
    }

    private function readCache(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        $json = file_get_contents($this->cacheFile);
        $data = json_decode($json, true);

        if (!$data || !isset($data['fetched_at'])) {
            return null;
        }

        $fetchedAt = strtotime($data['fetched_at']);
        if ((time() - $fetchedAt) > $this->cacheTTL) {
            return null;
        }

        return $data;
    }

    private function writeCache(array $data): void
    {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}
