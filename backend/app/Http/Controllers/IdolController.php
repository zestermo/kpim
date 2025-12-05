<?php

namespace App\Http\Controllers;

use App\Models\Idol;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdolController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $idols = $request->user()->playerProfile->idols()
            ->with('groups')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'idols' => $idols,
            ],
        ]);
    }

    public function scout(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        // Check if player can afford scouting
        if ($player->money < Idol::SCOUT_COST) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money to scout. Cost: ' . Idol::SCOUT_COST,
            ], 400);
        }

        // Deduct cost
        $player->spendMoney(Idol::SCOUT_COST);

        // Calculate quality bonus from manager
        $qualityBonus = $player->getManagerBonus(Manager::BONUS_SCOUTING);

        // Generate random idol
        $idolData = Idol::generateRandom(null, $qualityBonus);
        $idolData['player_profile_id'] = $player->id;

        $idol = Idol::create($idolData);

        // Add experience for scouting
        $player->addExperience(10);

        return response()->json([
            'success' => true,
            'data' => [
                'idol' => $idol,
                'player' => $player->fresh(),
                'message' => 'Successfully scouted ' . $idol->name . '!',
            ],
        ], 201);
    }

    public function train(Request $request, Idol $idol): JsonResponse
    {
        $player = $request->user()->playerProfile;

        // Verify ownership
        if ($idol->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This idol does not belong to you.',
            ], 403);
        }

        // Check if already training
        if ($idol->isTraining()) {
            return response()->json([
                'success' => false,
                'code' => 'ALREADY_TRAINING',
                'message' => 'This idol is already training.',
            ], 400);
        }

        $validated = $request->validate([
            'stat' => 'required|in:vocal,dance,visual,charm,stamina',
        ]);

        $trainingCost = 1000;
        $trainingMinutes = 2;

        // Apply training speed bonus
        $speedBonus = $player->getManagerBonus(Manager::BONUS_TRAINING);
        $trainingMinutes = (int) ($trainingMinutes * (1 - $speedBonus));
        $trainingMinutes = max(1, $trainingMinutes);

        if ($player->money < $trainingCost) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money for training.',
            ], 400);
        }

        $player->spendMoney($trainingCost);

        // Start training
        $idol->update([
            'training_until' => now()->addMinutes($trainingMinutes),
        ]);

        // Increase stat
        $statIncrease = rand(1, 5);
        $newValue = min(100, $idol->{$validated['stat']} + $statIncrease);
        $idol->update([$validated['stat'] => $newValue]);

        return response()->json([
            'success' => true,
            'data' => [
                'idol' => $idol->fresh(),
                'stat_increased' => $validated['stat'],
                'increase_amount' => $statIncrease,
                'training_ends_at' => $idol->training_until,
                'message' => $idol->name . "'s " . $validated['stat'] . " increased by " . $statIncrease,
            ],
        ]);
    }

    public function release(Request $request, Idol $idol): JsonResponse
    {
        $player = $request->user()->playerProfile;

        // Verify ownership
        if ($idol->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This idol does not belong to you.',
            ], 403);
        }

        // Check if in a group
        if ($idol->isInGroup()) {
            return response()->json([
                'success' => false,
                'code' => 'IN_GROUP',
                'message' => 'Remove idol from all groups before releasing.',
            ], 400);
        }

        $name = $idol->name;
        $idol->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $name . ' has been released from your agency.',
            ],
        ]);
    }
}

