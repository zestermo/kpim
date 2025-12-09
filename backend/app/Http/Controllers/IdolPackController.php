<?php

namespace App\Http\Controllers;

use App\Models\Idol;
use App\Models\Manager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IdolPackController extends Controller
{
    // Simple in-memory cache pack lifetime (seconds)
    private const PACK_TTL = 600;
    private const PACK_SIZE = 5;

    public function createPack(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        $packCost = Idol::PACK_COST;

        if ($player->money < $packCost) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money to buy a pack. Cost: ' . $packCost,
            ], 400);
        }

        $qualityBonus = $player->getManagerBonus(Manager::BONUS_SCOUTING);

        $drafts = [];
        for ($i = 0; $i < self::PACK_SIZE; $i++) {
            $drafts[] = Idol::generateRandom(null, $qualityBonus);
        }

        $packId = (string) Str::uuid();

        Cache::put($this->cacheKey($packId), [
            'player_id' => $player->id,
            'drafts' => $drafts,
            'cost' => $packCost,
        ], self::PACK_TTL);

        return response()->json([
            'success' => true,
            'data' => [
                'pack_id' => $packId,
                'cost' => $packCost,
                'idols' => $drafts,
                'expires_in' => self::PACK_TTL,
            ],
        ]);
    }

    public function chooseIdol(Request $request, string $pack): JsonResponse
    {
        $player = $request->user()->playerProfile;

        $validated = $request->validate([
            'index' => 'required|integer|min:0|max:' . (self::PACK_SIZE - 1),
        ]);

        $cached = Cache::get($this->cacheKey($pack));

        if (!$cached) {
            return response()->json([
                'success' => false,
                'code' => 'PACK_EXPIRED',
                'message' => 'This pack has expired. Please open a new pack.',
            ], 400);
        }

        if ($cached['player_id'] !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This pack does not belong to you.',
            ], 403);
        }

        // Check funds again in case balance changed
        if ($player->money < $cached['cost']) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money to claim this pack.',
            ], 400);
        }

        $drafts = $cached['drafts'];
        $index = $validated['index'];
        $chosen = $drafts[$index] ?? null;

        if (!$chosen) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_SELECTION',
                'message' => 'Invalid idol selection.',
            ], 400);
        }

        // Deduct cost once
        $player->spendMoney($cached['cost']);

        // Save chosen idol
        $chosen['player_profile_id'] = $player->id;
        $idol = Idol::create($chosen);

        // XP reward for scouting pack
        $player->addExperience(10);

        // Invalidate pack
        Cache::forget($this->cacheKey($pack));

        return response()->json([
            'success' => true,
            'data' => [
                'idol' => $idol,
                'player' => $player->fresh(),
                'message' => 'You recruited ' . $idol->name . '!',
            ],
        ]);
    }

    private function cacheKey(string $packId): string
    {
        return 'idol_pack_' . $packId;
    }
}

