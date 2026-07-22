<?php

namespace Tests\Feature;

use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    protected AdminRepositoryInterface $adminRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRepo = $this->app->make(AdminRepositoryInterface::class);

        // Clear existing test admins to prevent contamination
        $admins = $this->adminRepo->getAll();
        foreach ($admins as $admin) {
            $this->adminRepo->delete($admin['id']);
        }
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'secret123'
        ]);

        $response->assertStatus(401)
                 ->assertJsonStructure(['message']);
    }

    public function test_login_succeeds_with_valid_credentials()
    {
        // Seed an admin operator
        $admin = $this->adminRepo->create([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'Super Admin',
            'permissions' => ['*'],
            'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Test'
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'admin']);
        
        $this->assertEquals('test@example.com', $response->json('admin.email'));
    }

    public function test_profile_requires_jwt_authentication()
    {
        $response = $this->getJson('/api/auth/profile');
        $response->assertStatus(401);
    }
}
