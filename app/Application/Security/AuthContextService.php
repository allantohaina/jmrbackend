<?php

namespace App\Application\Security;

use App\Application\Shared\Result;
use App\Libraries\JWTLibrary;
use App\Models\TokenBlacklistModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;

class AuthContextService
{
    public function authenticate(RequestInterface $request): Result
    {
        $jwt = new JWTLibrary();

        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $authHeader = $request->getHeaderLine('X-Authorization');
        }
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return Result::unauthorized(['error' => lang('Auth.token.missing')]);
        }

        $token = $matches[1];
        $decoded = $jwt->decode($token);

        if (!$decoded) {
            return Result::unauthorized(['error' => lang('Auth.token.invalid_or_expired')]);
        }

        if (!isset($decoded->jti)) {
            return Result::unauthorized(['error' => lang('Auth.token.invalid')]);
        }

        $blacklist = new TokenBlacklistModel();
        $revoked = $blacklist->where('jti', $decoded->jti)->first();
        if ($revoked) {
            return Result::unauthorized(['error' => lang('Auth.token.revoked')]);
        }

        $model = new UserModel();
        $user = $model->getUserById($decoded->user_id ?? '');

        if (!$user || empty($user['is_active'])) {
            return Result::unauthorized(['error' => lang('Auth.user.inactive')]);
        }

        return Result::ok($user);
    }
}

