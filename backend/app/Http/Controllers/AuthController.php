<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PlayerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'agency_name' => 'nullable|string|max:255',
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

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->load('playerProfile'),
                'message' => 'Registration successful',
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user()->load('playerProfile.manager');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'message' => 'Login successful',
            ],
        ]);
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

