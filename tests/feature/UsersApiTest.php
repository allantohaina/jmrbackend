<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class UsersApiTest extends CIUnitTestCase
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

    public function testRegisterLoginProfileFlow()
    {
        $email = 'user_' . uniqid() . '@example.com';
        $password = 'Test123456';

        $register = $this->postJson('/api/users/register', [
            'email' => $email,
            'password' => $password,
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+1234567890',
        ]);

        $register->assertStatus(201);
        $registerData = json_decode($register->getJSON(), true);
        $this->assertSame($email, $registerData['user']['email'] ?? null);
        $token = $registerData['token'] ?? null;
        $this->assertNotEmpty($token);

        $login = $this->postJson('/api/users/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200);
        $loginToken = json_decode($login->getJSON(), true)['token'] ?? null;
        $this->assertNotEmpty($loginToken);

        $profile = $this->withAuth($loginToken)
            ->get('/api/users/profile');
        $profile->assertStatus(200);
        $profile->assertJSONFragment(['email' => $email]);
    }

    public function testProfileRequiresAuth()
    {
        $profile = $this->get('/api/users/profile');
        $profile->assertStatus(401);
    }

    public function testProfileUpdateRequiresAllFields()
    {
        $email = 'user_' . uniqid() . '@example.com';
        $password = 'Test123456';

        $register = $this->postJson('/api/users/register', [
            'email' => $email,
            'password' => $password,
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $register->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200);
        $token = json_decode($login->getJSON(), true)['token'] ?? null;
        $this->assertNotEmpty($token);

        $update = $this->withAuthJson($token)
            ->withBody(json_encode(['first_name' => 'Only']))
            ->put('/api/users/profile');
        $update->assertStatus(400);
        $payload = json_decode($update->getJSON(), true);
        $this->assertSame('Champs requis manquants', $payload['messages']['error'] ?? null);
    }

    public function testAdminRoutesRequireAdmin()
    {
        $email = 'user_' . uniqid() . '@example.com';
        $password = 'Test123456';

        $register = $this->postJson('/api/users/register', [
            'email' => $email,
            'password' => $password,
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $token = json_decode($register->getJSON(), true)['token'] ?? null;
        $this->assertNotEmpty($token);

        $list = $this->withAuth($token)
            ->get('/api/users');
        $list->assertStatus(403);
    }

    public function testAdminRoutesWithAdminUser()
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
        $token = $data['token'] ?? null;
        $userId = $data['user']['id'] ?? null;
        $this->assertNotEmpty($token);
        $this->assertNotEmpty($userId);

        $db = db_connect();
        $db->table('users')->where('id', $userId)->update(['role' => 'admin']);

        $login = $this->postJson('/api/users/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $login->assertStatus(200);
        $adminToken = json_decode($login->getJSON(), true)['token'] ?? null;
        $this->assertNotEmpty($adminToken);

        $list = $this->withAuth($adminToken)
            ->get('/api/users');
        $list->assertStatus(200);

        $show = $this->withAuth($adminToken)
            ->get('/api/users/' . $userId);
        $show->assertStatus(200);

        $update = $this->withAuthJson($adminToken)
            ->withBody(json_encode([
                'email' => $email,
                'first_name' => 'Admin2',
                'last_name' => 'User2',
                'is_active' => true,
            ]))
            ->put('/api/users/' . $userId);
        $update->assertStatus(200);

        $delete = $this->withAuth($adminToken)
            ->delete('/api/users/' . $userId);
        $delete->assertStatus(200);
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

    private function withAuthJson(string $token)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ]);
    }
}
