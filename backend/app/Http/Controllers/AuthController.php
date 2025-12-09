<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PlayerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'agency_name' => 'nullable|string|max:255',
            ]);

            Log::info('Register attempt', [
                'email' => $validated['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $user = DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                PlayerProfile::create([
                    'user_id' => $user->id,
                    'agency_name' => $validated['agency_name'] ?? $validated['name'] . "'s Agency",
                    'money' => PlayerProfile::STARTING_MONEY,
                    'fans' => PlayerProfile::STARTING_FANS,
                    'reputation' => PlayerProfile::STARTING_REPUTATION,
                    'level' => 1,
                    'experience' => 0,
                ]);

                return $user;
            });

            Auth::login($user);

            Log::info('Register success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'starting_money' => $user->playerProfile?->money,
            ]);

            $user->load('playerProfile.manager');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $this->formatUser($user),
                    'message' => 'Registration successful',
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Register failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            Log::info('Login attempt', [
                'email' => $validated['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if (!Auth::attempt($validated)) {
                Log::warning('Login failed: bad credentials', [
                    'email' => $validated['email'],
                ]);

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $request->session()->regenerate();

            $user = Auth::user();
            $this->ensurePlayerProfile($user);
            $user->load('playerProfile.manager', 'playerProfile.idols', 'playerProfile.groups.members');

            Log::info('Login success', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $this->formatUser($user),
                    'message' => 'Login successful',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'email' => $request->input('email'),
            ]);

            if ($e instanceof ValidationException) {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Logged out successfully',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->ensurePlayerProfile($request->user());
        $user->load('playerProfile.manager', 'playerProfile.idols', 'playerProfile.groups.members');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    private function ensurePlayerProfile(User $user): User
    {
        if (!$user->playerProfile) {
            Log::warning('Missing player profile, recreating', ['user_id' => $user->id, 'email' => $user->email]);
            PlayerProfile::create([
                'user_id' => $user->id,
                'agency_name' => $user->name . "'s Agency",
                'money' => PlayerProfile::STARTING_MONEY,
                'fans' => PlayerProfile::STARTING_FANS,
                'reputation' => PlayerProfile::STARTING_REPUTATION,
                'level' => 1,
                'experience' => 0,
            ]);
            $user->load('playerProfile');
        }

        if ($user->playerProfile && ($user->playerProfile->money === null || $user->playerProfile->money <= 0)) {
            Log::warning('Resetting player money to starting amount', [
                'user_id' => $user->id,
                'email' => $user->email,
                'current_money' => $user->playerProfile->money,
                'reset_to' => PlayerProfile::STARTING_MONEY,
            ]);
            $user->playerProfile->update(['money' => PlayerProfile::STARTING_MONEY]);
        }

        Log::info('ensurePlayerProfile status', [
            'user_id' => $user->id,
            'email' => $user->email,
            'money' => $user->playerProfile?->money,
            'fans' => $user->playerProfile?->fans,
            'reputation' => $user->playerProfile?->reputation,
        ]);

        return $user;
    }

    private function formatUser(User $user): array
    {
        $arr = $user->toArray();
        if (isset($arr['player_profile'])) {
            $arr['playerProfile'] = $arr['player_profile'];
            unset($arr['player_profile']);
        }
        return $arr;
    }
}

