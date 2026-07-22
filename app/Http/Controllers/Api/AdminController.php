<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    protected AdminRepositoryInterface $adminRepo;

    public function __construct(AdminRepositoryInterface $adminRepo)
    {
        $this->adminRepo = $adminRepo;
    }

    public function index()
    {
        $admins = $this->adminRepo->getAll();
        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:Super Admin,Admin,Viewer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if email exists
        $existing = $this->adminRepo->findByEmail($request->email);
        if ($existing) {
            return response()->json(['errors' => ['email' => ['This email is already registered.']]], 422);
        }

        $permissions = match($request->role) {
            'Super Admin' => ['*'],
            'Admin' => ['feedback.*', 'users.*', 'notifications.*', 'settings.read'],
            'Viewer' => ['feedback.read', 'users.read', 'notifications.read'],
            default => ['feedback.read'],
        };

        $admin = $this->adminRepo->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'permissions' => $permissions,
            'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($request->name),
        ]);

        unset($admin['password']);

        return response()->json([
            'message' => 'Admin user created successfully',
            'admin' => $admin
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|in:Super Admin,Admin,Viewer',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existing = $this->adminRepo->findById($id);
        if (!$existing) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $payload = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->password);
        }

        $payload['permissions'] = match($request->role) {
            'Super Admin' => ['*'],
            'Admin' => ['feedback.*', 'users.*', 'notifications.*', 'settings.read'],
            'Viewer' => ['feedback.read', 'users.read', 'notifications.read'],
            default => ['feedback.read'],
        };

        $this->adminRepo->update($id, $payload);

        return response()->json(['message' => 'Admin user updated successfully']);
    }

    public function destroy(string $id)
    {
        $admin = $this->adminRepo->findById($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        // Prevent deleting self or if it's the last Super Admin
        $currentAdmin = request()->attributes->get('admin');
        if ($currentAdmin && $currentAdmin['id'] === $id) {
            return response()->json(['message' => 'You cannot delete your own account'], 400);
        }

        $this->adminRepo->delete($id);
        return response()->json(['message' => 'Admin user deleted successfully']);
    }
}
