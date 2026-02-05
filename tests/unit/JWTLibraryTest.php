<?php

namespace Tests\Unit;

use App\Libraries\JWTLibrary;
use CodeIgniter\Test\CIUnitTestCase;

class JWTLibraryTest extends CIUnitTestCase
{
    public function testEncodeDecodeRoundTrip()
    {
        $jwt = new JWTLibrary();

        $token = $jwt->encode([
            'user_id' => 'user-123',
            'email'   => 'user@example.com',
            'role'    => 'user',
        ]);

        $decoded = $jwt->decode($token);

        $this->assertNotNull($decoded);
        $this->assertSame('user-123', $decoded->user_id);
        $this->assertSame('user@example.com', $decoded->email);
        $this->assertSame('user', $decoded->role);
        $this->assertTrue(isset($decoded->iat));
        $this->assertTrue(isset($decoded->exp));
        $this->assertGreaterThan($decoded->iat, $decoded->exp);
    }

    public function testDecodeFailsForTamperedToken()
    {
        $jwt = new JWTLibrary();

        $token = $jwt->encode([
            'user_id' => 'user-123',
            'email'   => 'user@example.com',
            'role'    => 'user',
        ]);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        $tamperedPayload = $parts[1] . 'x';
        $tampered = $parts[0] . '.' . $tamperedPayload . '.' . $parts[2];

        $decoded = $jwt->decode($tampered);
        $this->assertNull($decoded);
    }
}
