<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function index(): JsonResponse
    {
        $managers = Manager::all();

        return response()->json([
            'success' => true,
            'data' => [
                'managers' => $managers,
            ],
        ]);
    }

    public function select(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:managers,id',
        ]);

        $player = $request->user()->playerProfile;
        
        // Allow selection (removed the "already selected" restriction for now)
        $player->manager_id = $validated['manager_id'];
        $player->save();

        return response()->json([
            'success' => true,
            'data' => [
                'player' => $player->fresh()->load('manager'),
                'message' => 'Manager selected successfully',
            ],
        ]);
    }
}
