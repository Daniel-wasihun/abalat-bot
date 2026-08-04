<?php

namespace App\Http\Controllers;

use App\Helpers\Permission;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSession;
use App\Models\LoginThrottle;
use App\Services\BackMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Helpers\Response;
use Illuminate\Support\Facades\DB;
use App\Constants\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AuthController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware(Permission::users()->create(), only: ['register']),
        ];
    }
    /**
     * Register a new user
     */
    public function register(UserRequest $request) {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth('api')->user(); // The creator (admin)

        try {
            $user = DB::transaction(function () use ($request, $currentUser) {
                $user = User::create([
                    'name'     => ['en' => $request->name],
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'is_active' => true,
                ]);

                // Create User Info
                $userInfoData = [
                    'user_university_id' => $request->user_university_id,
                    'user_type'          => $request->user_type ?? Type::STUDENT,
                    'gender'             => $request->gender,
                    'phone_number'       => $request->phone_number,
                    'date_of_birth'      => $request->date_of_birth,
                    'address'            => $request->address,
                ];

                if ($request->hasFile('profile_picture')) {
                    $path = $request->file('profile_picture')->store('profile_pictures', 'public');
                    $userInfoData['profile_picture'] = $path;
                }

                $user->info()->create($userInfoData);

                // Assign role
                if ($request->filled('role')) {
                    $role = Role::where('slug', $request->role)->firstOrFail();

                    if ($currentUser && !$currentUser->canModifyRole($role)) {
                        throw new \Exception(BackMessage::get('forbidden'), 403);
                    }

                    $user->roles()->sync([
                        $role->id => [
                            'assigned_by' => $currentUser->id,
                            'start_date'  => $request->start_date ?? now(),
                            'end_date'    => $request->end_date,
                            'is_active'   => true,
                        ]
                    ]);
                }

                // Grant direct permissions
                if ($request->filled('permissions')) {
                    $permissions = Permission::whereIn('slug', $request->permissions)->get();
                    $syncData = [];
                    foreach ($permissions as $permission) {
                        $syncData[$permission->id] = [
                            'granted'     => true,
                            'assigned_by' => $currentUser->id,
                            'start_date'  => $request->start_date ?? now(),
                            'end_date'    => $request->end_date,
                        ];
                    }
                    $user->directPermissions()->sync($syncData);
                }

                return $user;
            });

            // Send email with password (unhashed)
            // Note: $request->password contains the plain text password
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\UserRegisteredMail($user, $request->password));
            } catch (\Exception $me) {
                Log::error("Registration email delivery failure: " . $me->getMessage());
            }

            return Response::_201(
                UserResource::success($user->load(['info', 'roles', 'directPermissions']), 'register_success')
            );
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 422;
            if (!in_array($code, [403, 422])) $code = 422;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }

    /**
     * Login user and issue token
     */
    public function login(AuthRequest $request) {
        $ip = $request->ip();
        $email = $request->email;
        $userAgent = $request->userAgent();

        // 1. Check Throttle (Per Email + IP + Device)
        // This ensures login attempts are tracked separately for different devices or locations.
        $throttle = \App\Models\LoginThrottle::firstOrCreate(
            [
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent
            ]
        );

        // Check if locked
        if ($throttle->locked_until && now()->lessThan($throttle->locked_until)) {
            $diffInMinutes = now()->diffInMinutes($throttle->locked_until);
            // If less than 1 minute, show seconds
            if ($diffInMinutes < 1) {
                $seconds = now()->diffInSeconds($throttle->locked_until);
                return Response::_429(BackMessage::get('too_many_attempts_seconds', ['time' => ceil($seconds)]));
            }
            return Response::_429(BackMessage::get('too_many_attempts_minutes', ['time' => ceil($diffInMinutes)]));
        }

        // If lock expired, we might need to reset attempts or just proceed?
        // Logic: specific "rounds". If lock expired, they can try again.
        // If they fail again, we continue counting? No, typically once lock expires we reset attempts?
        // But the requirement says "second round", so we must persist lockout_count but reset attempts.
        if ($throttle->locked_until && now()->greaterThanOrEqualTo($throttle->locked_until)) {
            $throttle->update([
                'locked_until' => null,
                'attempts' => 0
            ]);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Verify credentials
        if (!$user || !Hash::check($request->password, $user->password)) {

            // Increment attempts
            $throttle->increment('attempts');
            $throttle->refresh();

            // Check if we need to lock
            if ($throttle->attempts >= 5) {
                // Determine lock duration based on previous lockouts
                // lockout_count starts at 0.
                // 1st lockout (count becomes 1) -> 15 min
                // 2nd lockout (count becomes 2) -> 1 hour
                // 3rd lockout (count becomes 3+) -> 1 day

                $throttle->increment('lockout_count');
                $throttle->refresh();
                $count = $throttle->lockout_count; // Now it's 1, 2, or 3...

                $duration = 15; // default minutes
                $msgKey = 'too_many_attempts_minutes';
                $timeVal = 15;

                if ($count == 1) {
                    $duration = 15; // 15 min
                    $msgKey = 'too_many_attempts_minutes';
                    $timeVal = 15;
                } elseif ($count == 2) {
                    $duration = 60; // 1 hour
                    $msgKey = 'too_many_attempts_minutes'; // We can use minutes or add an hour key
                    $timeVal = 60;
                } else {
                    $duration = 1440; // 24 hours
                    $msgKey = 'too_many_attempts_hours';
                    $timeVal = 24;
                }

                $throttle->update([
                    'locked_until' => now()->addMinutes($duration),
                    'attempts' => 0 // Reset attempts so they start fresh after lock expires
                ]);

                return Response::_429(BackMessage::get($msgKey, ['time' => $timeVal]));
            }

            $remaining = 5 - $throttle->attempts;
            return Response::_401(BackMessage::get('invalid_credentials') . " " . BackMessage::get('attempts_remaining', ['attempts' => $remaining]));
        }

        if (!$user->is_active) {
            return Response::_403(BackMessage::get('inactive_account'));
        }

        // Login Success: 
        // We reset the attempts to 0 so they have 5 trials again for the next session.
        // We do NOT delete the record or reset lockout_count, to preserve the "Round" escalation (15m -> 1h -> 24h)
        // if they continue to have issues in the future.
        $throttle->update([
            'attempts' => 0,
            'locked_until' => null
        ]);

        // Load relationships
        $user->load(['info', 'roles', 'directPermissions']);

        // Create token
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->accessToken;
        $tokenId = $tokenResult->token->id;

        return Response::_200(
            UserResource::success($user, 'login_successfully')
                ->withToken($token, $tokenId)
        );
    }

    /**
     * Logout user (revoke all tokens)
     */
    public function logout(AuthRequest $request) {
        $user    = $request->user();
        $tokenId = $this->resolveCurrentTokenId($user);

        // Mark the current session as logged-out (NOT terminated) so that
        // a subsequent login from this same device is not flagged as suspicious.
        if ($tokenId) {
            UserSession::where('user_id', $user->id)
                ->where('session_id', $tokenId)
                ->where('status', UserSession::STATUS_ACTIVE)
                ->first()
                ?->markAsLoggedOut();
        }

        $user->tokens()->delete();

        return Response::_200(['message' => BackMessage::get('logout_success')]);
    }

    /**
     * Get current authenticated user
     */
    public function me() {
        // Get authenticated user
        $user = auth('api')->user();

        // Return unauthenticated response if no user found
        if (!$user) {
            return Response::_401(BackMessage::get('unauthenticated'));
        }

        // Safely load relationships - double check user is still valid
        if ($user instanceof User) {
            $user->load(['roles.permissions', 'directPermissions', 'sessions' => function ($query) {
                $query->where('is_active', true)->orderBy('last_active_at', 'desc');
            }]);
        }

        return Response::_200(UserResource::success($user));
    }

    /**
     * Change password for the current user
     */
    public function changePassword(ChangePasswordRequest $request) {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return Response::_422(BackMessage::get('validation_error'), [
                'current_password' => BackMessage::get('incorrect_current_password')
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return Response::_200(['message' => BackMessage::get('password_changed_success')]);
    }

    /**
     * Update user profile (Self Update)
     */
    public function updateProfile(\App\Http\Requests\ProfileUpdateRequest $request) {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $data = $request->validated();

        DB::transaction(function () use ($user, $data, $request) {
            // Update User Table: Preserve other translations if present
            if (!empty($data['name'])) {
                $currentName = $user->name ?? [];
                if (!is_array($currentName)) $currentName = ['en' => $currentName];
                $currentName['en'] = $data['name'];
                $user->update(['name' => $currentName]);
            }

            // Prepare UserInfo data
            // Only allowed fields: address, date_of_birth, profile_picture
            $infoData = [
                'address' => $data['address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
            ];

            // Handle Profile Picture Upload
            if ($request->hasFile('profile_picture')) {
                // Delete old picture if exists
                if ($user->info && $user->info->profile_picture) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->info->profile_picture);
                }

                $path = $request->file('profile_picture')->store('profile_pictures', 'public');
                $infoData['profile_picture'] = $path;
            }

            // Update or Create UserInfo
            $userInfo = $user->info;

            if ($userInfo) {
                $toUpdate = [];
                if (array_key_exists('address', $data)) $toUpdate['address'] = $data['address'];
                if (array_key_exists('date_of_birth', $data)) $toUpdate['date_of_birth'] = $data['date_of_birth'];
                if (isset($infoData['profile_picture'])) $toUpdate['profile_picture'] = $infoData['profile_picture'];
                if (array_key_exists('gender', $data)) $toUpdate['gender'] = $data['gender'];

                $userInfo->update($toUpdate);
            } else {
                // Determine sensible defaults if missing
                $createData = [
                    'user_university_id' => 'EXT-' . $user->id . '-' . mt_rand(1000, 9999),
                    'user_type' => 'student', // Admin can change this later
                    'gender' => $data['gender'] ?? 'male',
                    'date_of_birth' => $infoData['date_of_birth'] ?? null,
                    'address' => $infoData['address'] ?? null,
                ];
                if (isset($infoData['profile_picture'])) $createData['profile_picture'] = $infoData['profile_picture'];

                $user->info()->create($createData);
            }
        });

        return Response::_200([
            'message' => BackMessage::get('profile.update_success'),
            'user' => new UserResource($user->load(['info', 'roles', 'directPermissions', 'sessions' => function ($query) {
                $query->where('is_active', true)->orderBy('last_active_at', 'desc');
            }]))
        ]);
    }

    /**
     * Get active sessions for the current user
     */
    public function sessions() {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $user->load(['sessions' => function ($query) {
            $query->where('is_active', true)->orderBy('last_active_at', 'desc');
        }]);

        return Response::_200(UserResource::success($user));
    }

    /**
     * Logout from a specific session
     */
    public function logoutSession(Request $request, $id) {
        $user = $request->user();
        $targetSession = UserSession::where('user_id', $user->id)->find($id);

        if (!$targetSession) {
            return Response::_404('Session not found');
        }

        $currentTokenId = $this->resolveCurrentTokenId($user);
        $currentSession = $currentTokenId
            ? UserSession::where('user_id', $user->id)->where('session_id', $currentTokenId)->first()
            : null;

        // 🛡️ Security Rule: Seniority & Establishment Check
        if ($currentSession && $currentSession->created_at->gt(now()->subMonth())) {
            // New device (< 1 month) cannot terminate sessions that are older than itself
            if ($targetSession->created_at->lt($currentSession->created_at)) {
                return Response::_403(BackMessage::get('notifications.session_termination_restricted'));
            }
        }

        // Revoke the Passport token associated with this session
        if ($targetSession->session_id) {
            DB::table('oauth_access_tokens')
                ->where('id', $targetSession->session_id)
                ->update(['revoked' => true]);
        }

        // Kicking another device's session from the devices panel is a
        // force-termination, NOT a normal logout.  The next login from that
        // device will trigger the more-alarming "terminated relogin" alert.
        $targetSession->markAsTerminated();

        // 📡 Broadcast termination to the affected device for real-time logout
        try {
            event(new \App\Events\SessionTerminated($user->id, $targetSession->session_id));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Session termination broadcast failed: ' . $e->getMessage());
        }

        return Response::_200(['message' => BackMessage::get('notifications.session_logout_success')]);
    }

    /**
     * Logout from all sessions except the current one
     */
    public function logoutAllOtherSessions(Request $request) {
        $user = $request->user();
        $currentTokenId = $this->resolveCurrentTokenId($user);

        if (!$currentTokenId) {
            return Response::_401('Current session not identified');
        }

        $currentSession = UserSession::where('user_id', $user->id)
            ->where('session_id', $currentTokenId)
            ->first();

        $isNewDevice = $currentSession
            && $currentSession->created_at
            && $currentSession->created_at->gt(now()->subMonth());

        $query = UserSession::where('user_id', $user->id)
            ->where('session_id', '!=', $currentTokenId)
            ->where('is_active', true);

        // If it's a new device, it can only logout other sessions that are STRICTLY NEWER than itself
        if ($isNewDevice && $currentSession) {
            $query->where('created_at', '>', $currentSession->created_at);
        }

        $sessionsToLogout = $query->get();
        $sessionIds = $sessionsToLogout->pluck('session_id')->filter()->toArray();

        if (!empty($sessionIds)) {
            DB::table('oauth_access_tokens')
                ->whereIn('id', $sessionIds)
                ->update(['revoked' => true]);
        }

        // Mark as terminated (not logged-out): these sessions are being
        // force-ended by another device, so future logins from those devices
        // will trigger the "terminated relogin" security notification.
        UserSession::where('user_id', $user->id)
            ->where('session_id', '!=', $currentTokenId)
            ->where('status', UserSession::STATUS_ACTIVE)
            ->when($isNewDevice, fn($q) => $q->where('created_at', '>', $currentSession->created_at))
            ->update(['status' => UserSession::STATUS_TERMINATED, 'is_active' => false]);

        // 📡 Broadcast termination to all affected devices
        foreach ($sessionIds as $sid) {
            try {
                event(new \App\Events\SessionTerminated($user->id, $sid));
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        $message = $isNewDevice
            ? BackMessage::get('notifications.session_termination_partially_restricted')
            : BackMessage::get('notifications.all_sessions_logout_success');

        return Response::_200(['message' => $message]);
    }

    /**
     * Resolve the current Passport token ID from the authenticated user.
     */
    private function resolveCurrentTokenId($user): ?string {
        $token = $user->token();
        return $token ? (string)($token->oauth_access_token_id ?? $token->id) : null;
    }
}
