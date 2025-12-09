<?php

namespace App\Http\Controllers;

use App\Models\AgencyUpgrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgencyUpgradeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile()->with('agencyUpgrades')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'upgrades' => AgencyUpgrade::getCatalogWithProgress($player),
                'player' => $player->fresh(),
            ],
        ]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile()->with('agencyUpgrades')->first();

        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(AgencyUpgrade::CONFIG)),
        ]);

        $type = $validated['type'];
        $config = AgencyUpgrade::CONFIG[$type];

        return DB::transaction(function () use ($player, $type, $config) {
            $upgrade = $player->agencyUpgrades()->firstOrCreate(
                ['type' => $type],
                ['level' => 0]
            );

            if ($upgrade->level >= $config['max_level']) {
                return response()->json([
                    'success' => false,
                    'code' => 'MAX_LEVEL',
                    'message' => 'Upgrade already at max level.',
                ], 400);
            }

            $cost = AgencyUpgrade::costForNextLevel($type, $upgrade->level);

            if ($player->fans < $cost['fans'] || $player->reputation < $cost['reputation']) {
                return response()->json([
                    'success' => false,
                    'code' => 'INSUFFICIENT_RESOURCES',
                    'message' => 'Not enough fans or reputation for this upgrade.',
                    'meta' => [
                        'required_fans' => $cost['fans'],
                        'required_reputation' => $cost['reputation'],
                        'current_fans' => $player->fans,
                        'current_reputation' => $player->reputation,
                    ],
                ], 400);
            }

            // Spend resources
            $player->decrement('fans', $cost['fans']);
            $player->decrement('reputation', $cost['reputation']);

            $upgrade->increment('level');

            return response()->json([
                'success' => true,
                'data' => [
                    'upgrades' => AgencyUpgrade::getCatalogWithProgress($player->fresh('agencyUpgrades')),
                    'player' => $player->fresh(),
                    'message' => 'Upgrade purchased!',
                ],
            ]);
        });
    }
}

