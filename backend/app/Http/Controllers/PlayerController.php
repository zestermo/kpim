<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile->load([
            'manager',
            'idols',
            'groups.members',
            'songs',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'player' => $player,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_name' => 'sometimes|string|max:255',
        ]);

        $player = $request->user()->playerProfile;
        $player->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'player' => $player->fresh(),
                'message' => 'Profile updated successfully',
            ],
        ]);
    }
}

