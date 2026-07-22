<?php

namespace App\Services;

use App\Repositories\Contracts\AdminRepositoryInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthService
{
    protected AdminRepositoryInterface $adminRepo;
    protected string $jwtSecret;

    public function __construct(AdminRepositoryInterface $adminRepo)
    {
        $this->adminRepo = $adminRepo;
        $this->jwtSecret = env('JWT_SECRET', 'c8e6a17b9d42f5310e2d78a9c40f1a234567890abcdef1234567890abcdef');
        $this->ensureSuperAdminExists();
    }

    public function ensureSuperAdminExists(): void
    {
        $admins = $this->adminRepo->getAll();
        if (empty($admins)) {
            $this->adminRepo->create([
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Super Admin',
                'permissions' => ['*'],
                'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=SuperAdmin',
            ]);
        }
    }

    public function login(string $email, string $password): ?array
    {
        $admin = $this->adminRepo->findByEmail($email);
        if (!$admin) {
            return null;
        }

        if (!Hash::check($password, $admin['password'])) {
            return null;
        }

        $token = $this->generateJwtToken($admin);

        unset($admin['password']);

        return [
            'token' => $token,
            'admin' => $admin,
        ];
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $adminId = $decoded->sub ?? null;
            if (!$adminId) {
                return null;
            }
            $admin = $this->adminRepo->findById($adminId);
            if ($admin) {
                unset($admin['password']);
                return $admin;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function generatePasswordResetToken(string $email): ?string
    {
        $admin = $this->adminRepo->findByEmail($email);
        if (!$admin) {
            return null;
        }

        $resetToken = Str::random(40);
        $this->adminRepo->update($admin['id'], [
            'resetToken' => $resetToken,
            'resetTokenExpiresAt' => now()->addHour()->toIso8601String(),
        ]);

        return $resetToken;
    }

    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $admin = $this->adminRepo->findByEmail($email);
        if (!$admin) {
            return false;
        }

        if (($admin['resetToken'] ?? '') !== $token) {
            return false;
        }

        if (now()->isAfter($admin['resetTokenExpiresAt'] ?? now()->subMinute())) {
            return false;
        }

        $this->adminRepo->update($admin['id'], [
            'password' => Hash::make($newPassword),
            'resetToken' => null,
            'resetTokenExpiresAt' => null,
        ]);

        return true;
    }

    public function updateProfile(string $adminId, array $data): array
    {
        $this->adminRepo->update($adminId, $data);
        $updated = $this->adminRepo->findById($adminId);
        unset($updated['password']);
        return $updated;
    }

    public function changePassword(string $adminId, string $currentPassword, string $newPassword): bool
    {
        $admin = $this->adminRepo->findById($adminId);
        if (!$admin || !Hash::check($currentPassword, $admin['password'])) {
            return false;
        }

        $this->adminRepo->update($adminId, [
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }

    protected function generateJwtToken(array $admin): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $admin['id'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7), // 7 days
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}
