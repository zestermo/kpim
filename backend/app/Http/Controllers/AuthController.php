<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PlayerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Create player profile
            PlayerProfile::create([
                'user_id' => $user->id,
                'agency_name' => $validated['agency_name'] ?? $validated['name'] . "'s Agency",
                'money' => PlayerProfile::STARTING_MONEY,
                'fans' => PlayerProfile::STARTING_FANS,
                'reputation' => PlayerProfile::STARTING_REPUTATION,
            ]);

            Auth::login($user);

            Log::info('Register success', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->load('playerProfile'),
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

            $user = Auth::user()->load('playerProfile.manager');

            Log::info('Login success', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
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
        $user = $request->user()->load([
            'playerProfile.manager',
            'playerProfile.idols',
            'playerProfile.groups.members',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
            ],
        ]);
    }
}

