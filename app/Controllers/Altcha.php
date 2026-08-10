<?php

namespace App\Controllers;

use AltchaOrg\Altcha\Altcha as AltchaLib;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\HmacAlgorithm;
use AltchaOrg\Altcha\VerifySolutionOptions;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Altcha extends ResourceController
{
    protected $format = 'json';

    private ?AltchaLib $altcha = null;

    private function getAltcha(): AltchaLib
    {
        if ($this->altcha === null) {
            $secret = getenv('ALTCHA_SECRET') ?: 'jmr-altcha-change-me';
            $this->altcha = new AltchaLib(hmacSignatureSecret: $secret);
        }

        return $this->altcha;
    }

    public function challenge(): ResponseInterface
    {
        $altcha = $this->getAltcha();
        $algorithm = new Pbkdf2();

        $challenge = $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: $algorithm,
            cost: 5000,
            counter: random_int(5000, 10000),
            expiresAt: time() + 600,
        ));

        return $this->respond([
            'challenge' => [
                'algorithm' => $challenge->algorithm,
                'challenge' => $challenge->challenge,
                'salt' => $challenge->salt,
                'signature' => $challenge->signature,
                'maxN' => $challenge->maxN,
            ],
            'signature' => $challenge->signature,
        ]);
    }

    public function verify(): ResponseInterface
    {
        $payload = $this->request->getPost('altcha')
            ?? $this->request->getJSON(true)['altcha']
            ?? null;

        if (empty($payload)) {
            return $this->failUnauthorized('ALTCHA token manquant.');
        }

        $altcha = $this->getAltcha();
        $algorithm = new Pbkdf2();

        try {
            $result = $altcha->verifySolution(new VerifySolutionOptions(
                algorithm: $algorithm,
                payload: $payload,
            ));
        } catch (\Throwable $e) {
            return $this->failUnauthorized('ALTCHA invalide: ' . $e->getMessage());
        }

        if (!$result->verified) {
            $reason = $result->expired ? 'Le challenge ALTCHA a expiré.' : 'Vérification ALTCHA échouée.';
            return $this->failUnauthorized($reason);
        }

        return $this->respond(['verified' => true]);
    }
}
