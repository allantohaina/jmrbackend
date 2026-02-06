<?php

namespace App\Controllers;

use App\Application\Legal\LegalService;
use App\Application\Shared\Result;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Legal extends ResourceController
{
    protected $format = 'json';

    private ?LegalService $legalService = null;

    public function privacy()
    {
        return $this->respondResult($this->legalService()->getDoc('privacy'));
    }

    public function terms()
    {
        return $this->respondResult($this->legalService()->getDoc('terms'));
    }

    public function cookies()
    {
        return $this->respondResult($this->legalService()->getDoc('cookies'));
    }

    public function disclaimer()
    {
        return $this->respondResult($this->legalService()->getDoc('disclaimer'));
    }

    public function accessibility()
    {
        return $this->respondResult($this->legalService()->getDoc('accessibility'));
    }

    public function legalNotice()
    {
        return $this->respondResult($this->legalService()->getDoc('legal-notice'));
    }

    public function consent()
    {
        try {
            $result = $this->legalService()->recordConsent($this->getInputData(), $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->fail(lang('Legal.consent.error'), 500);
        }
    }

    public function dataRequest()
    {
        try {
            $result = $this->legalService()->recordDataRequest($this->getInputData(), $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->fail(lang('Legal.data_request.error'), 500);
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
                return $this->fail(lang('Common.errors.unexpected'), 500);
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

    private function legalService(): LegalService
    {
        if ($this->legalService === null) {
            $this->legalService = new LegalService();
        }

        return $this->legalService;
    }
}

