<?php

namespace App\Controllers;

use App\Application\Hr\HrService;
use App\Application\Shared\Result;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Hr extends ResourceController
{
    protected $format = 'json';

    private ?HrService $hrService = null;

    public function lookup($name = null)
    {
        try {
            return $this->respondResult($this->hrService()->lookup((string) $name));
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function departmentPostes($departmentId = null)
    {
        try {
            return $this->respondResult($this->hrService()->departmentPostes((string) $departmentId));
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function departmentManager($departmentId = null)
    {
        try {
            return $this->respondResult($this->hrService()->departmentManager((string) $departmentId));
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function createEmploye()
    {
        try {
            return $this->respondResult($this->hrService()->createEmploye($this->getInputData()));
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function respondResult(Result $result)
    {
        switch ($result->getType()) {
            case Result::TYPE_OK:
            case Result::TYPE_CREATED:
                return $this->respond($result->getPayload(), $result->getStatus());
            case Result::TYPE_NOT_FOUND:
                return $this->failNotFound((string) $result->getPayload());
            case Result::TYPE_UNAUTHORIZED:
            case Result::TYPE_FORBIDDEN:
            case Result::TYPE_FAIL:
                return $this->fail($result->getPayload(), $result->getStatus());
            default:
                return $this->fail('Erreur inattendue.', 500);
        }
    }

    private function getInputData(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $post = $this->request->getPost();

        return is_array($post) ? $post : [];
    }

    private function hrService(): HrService
    {
        if ($this->hrService === null) {
            $this->hrService = new HrService();
        }

        return $this->hrService;
    }

    private function handleException(Throwable $e)
    {
        log_message('error', 'HR controller failure: {message}', ['message' => $e->getMessage()]);

        return $this->respond([
            'message' => 'Une erreur interne est survenue.',
        ], 500);
    }
}
