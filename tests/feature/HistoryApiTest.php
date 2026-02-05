<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class HistoryApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $namespace = 'Tests\\Support';
    protected $refresh = true;
    protected $migrate = true;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('JWT_SECRET_KEY=test-secret-key');
    }

    public function testHistoryRequiresAdmin()
    {
        $resp = $this->get('/api/admin/history/audit');
        $resp->assertStatus(403);
    }

    public function testHistoryEndpointsAsAdmin()
    {
        $adminToken = $this->createAdminToken();

        $audit = $this->withAuth($adminToken)->get('/api/admin/history/audit');
        $audit->assertStatus(200);
        $audit->assertJSONFragment(['limit' => 50]);

        $tokens = $this->withAuth($adminToken)->get('/api/admin/history/tokens');
        $tokens->assertStatus(200);
        $tokens->assertJSONFragment(['limit' => 50]);

        $projects = $this->withAuth($adminToken)->get('/api/admin/history/projects');
        $projects->assertStatus(200);
        $projects->assertJSONFragment(['limit' => 50]);
    }

    private function createAdminToken(): string
    {
        $email = 'admin_' . uniqid() . '@example.com';
        $password = 'Test123456';

        $register = $this->postJson('/api/users/register', [
            'email' => $email,
            'password' => $password,
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $data = json_decode($register->getJSON(), true);
        $userId = $data['user']['id'] ?? null;

        $db = db_connect();
        $db->table('users')->where('id', $userId)->update(['role' => 'admin']);

        $login = $this->postJson('/api/users/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $loginData = json_decode($login->getJSON(), true);

        return $loginData['token'] ?? '';
    }

    private function postJson(string $uri, array $payload)
    {
        return $this->withBody(json_encode($payload))
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($uri);
    }

    private function withAuth(string $token)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
    }
}
