<?php

namespace App\Controllers;

use App\Application\Shared\Result;
use App\Application\Users\UserService;
use App\Exceptions\ApiException;
use App\Exceptions\UnknownException;
use App\Libraries\AltchaVerify;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class Users extends ResourceController
{
    protected $modelName = 'App\Models\UserModel';
    protected $format = 'json';

    private ?UserService $userService = null;

    public function register()
    {
        try {
            $input = $this->getInputData();
            $altchaResult = (new AltchaVerify())->verifyToken($input['altcha'] ?? null);
            if (!$altchaResult['verified']) {
                return $this->failUnauthorized($altchaResult['error']);
            }
            unset($input['altcha']);
            $result = $this->userService()->register($input, $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function login()
    {
        try {
            $input = $this->getInputData();
            $altchaResult = (new AltchaVerify())->verifyToken($input['altcha'] ?? null);
            if (!$altchaResult['verified']) {
                return $this->failUnauthorized($altchaResult['error']);
            }
            unset($input['altcha']);
            $result = $this->userService()->login($input, $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function profile()
    {
        try {
            $userId = $this->request->user['id'] ?? null;
            $result = $this->userService()->profile($userId);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function updateProfile()
    {
        try {
            $userId = $this->request->user['id'] ?? null;
            $result = $this->userService()->updateProfile($userId, $this->getInputData(), $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function deleteProfile()
    {
        try {
            $userId = $this->request->user['id'] ?? null;
            $result = $this->userService()->deleteProfile($userId, $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function index()
    {
        try {
            $result = $this->userService()->listUsers();
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id = null)
    {
        try {
            $result = $this->userService()->getUser($id);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $actorId = $this->request->user['id'] ?? null;
            $result = $this->userService()->updateUser($id, $this->getInputData(), $actorId, $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete($id = null)
    {
        try {
            $actorId = $this->request->user['id'] ?? null;
            $result = $this->userService()->deleteUser($id, $actorId, $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function refresh()
    {
        try {
            $input = $this->getInputData();
            $refreshToken = $input['refresh_token'] ?? null;
            $result = $this->userService()->refreshToken($refreshToken, $this->request);
            return $this->respondResult($result);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function logout()
    {
        try {
            $input = $this->getInputData();
            $refreshToken = $input['refresh_token'] ?? null;
            $authHeader = $this->request->getHeaderLine('Authorization');
            $result = $this->userService()->logout($refreshToken, $authHeader, $this->request);
            return $this->respondResult($result);
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

    private function userService(): UserService
    {
        if ($this->userService === null) {
            $this->userService = new UserService();
        }

        return $this->userService;
    }

    private function handleException(Throwable $e)
    {
        if ($e instanceof ApiException) {
            return $this->respond([
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ], $e->getStatusCode());
        }

        log_message(
            'error',
            '[Users::{method}] {type}: {message}' . PHP_EOL . '{trace}',
            [
                'method' => $this->request->getMethod(),
                'type' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]
        );

        $unknown = new UnknownException(lang('Common.errors.unexpected'), 0, $e);

        return $this->respond([
            'error' => $unknown->getMessage(),
        ], 500);
    }
}

