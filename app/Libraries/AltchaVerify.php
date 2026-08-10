<?php

namespace App\Libraries;

use AltchaOrg\Altcha\Altcha as AltchaLib;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\VerifySolutionOptions;

class AltchaVerify
{
    private ?AltchaLib $altcha = null;

    public function __construct()
    {
        try {
            $secret = getenv('ALTCHA_SECRET') ?: 'jmr-altcha-change-me';
            $this->altcha = new AltchaLib(hmacSignatureSecret: $secret);
        } catch (\Throwable $e) {
            log_message('error', 'ALTCHA lib init failed: ' . $e->getMessage());
            $this->altcha = null;
        }
    }

    /**
     * Verify an ALTCHA token from the request.
     *
     * @return array{verified: bool, error?: string}
     */
    public function verifyToken(?string $token): array
    {
        if ($this->altcha === null) {
            return ['verified' => true];
        }

        if (empty($token)) {
            return ['verified' => false, 'error' => 'Vérification de sécurité requise.'];
        }

        try {
            $result = $this->altcha->verifySolution(new VerifySolutionOptions(
                algorithm: new Pbkdf2(),
                payload: $token,
            ));
        } catch (\Throwable) {
            return ['verified' => false, 'error' => 'Vérification de sécurité invalide.'];
        }

        if (!$result->verified) {
            $error = $result->expired
                ? 'La vérification de sécurité a expiré. Veuillez réessayer.'
                : 'Vérification de sécurité échouée.';
            return ['verified' => false, 'error' => $error];
        }

        return ['verified' => true];
    }
}
