<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Manager;
use App\Models\Promotion;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $promotions = $request->user()->playerProfile->promotions()
            ->with(['group', 'song'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($promo) {
                return array_merge($promo->toArray(), [
                    'is_active' => $promo->isActive(),
                    'is_ready' => $promo->isReadyToComplete(),
                    'is_completed' => $promo->isCompleted(),
                ]);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'promotions' => $promotions,
            ],
        ]);
    }

    public function available(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'promotion_types' => Promotion::getAvailableTypes(),
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'song_id' => 'required|exists:songs,id',
            'type' => 'required|in:' . implode(',', array_keys(Promotion::PROMOTIONS)),
        ]);

        // Verify ownership
        $group = Group::find($validated['group_id']);
        $song = Song::find($validated['song_id']);

        if ($group->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This group does not belong to you.',
            ], 403);
        }

        if ($song->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This song does not belong to you.',
            ], 403);
        }

        // Song must be completed
        $song->checkCompletion();
        if (!$song->isCompleted()) {
            return response()->json([
                'success' => false,
                'code' => 'SONG_NOT_READY',
                'message' => 'Song is still in production.',
            ], 400);
        }

        // Song must belong to the group
        if ($song->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'code' => 'SONG_GROUP_MISMATCH',
                'message' => 'This song does not belong to this group.',
            ], 400);
        }

        $promoConfig = Promotion::PROMOTIONS[$validated['type']];

        // Check cost
        if ($player->money < $promoConfig['cost']) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money for this promotion. Cost: ' . $promoConfig['cost'],
            ], 400);
        }

        $player->spendMoney($promoConfig['cost']);

        // Calculate rewards
        $promotionBonus = $player->getManagerBonus(Manager::BONUS_PROMOTION);
        $viralityBonus = $player->getManagerBonus(Manager::BONUS_VIRALITY);
        
        $rewards = Promotion::calculateRewards(
            $validated['type'],
            $group,
            $song,
            $promotionBonus,
            $viralityBonus
        );

        $promotion = Promotion::create([
            'player_profile_id' => $player->id,
            'group_id' => $group->id,
            'song_id' => $song->id,
            'type' => $validated['type'],
            'cost' => $promoConfig['cost'],
            'fan_reward' => $rewards['fan_reward'],
            'money_reward' => $rewards['money_reward'],
            'reputation_reward' => $rewards['reputation_reward'],
            'went_viral' => $rewards['went_viral'],
            'started_at' => now(),
            'ends_at' => now()->addMinutes($promoConfig['duration_minutes']),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'promotion' => array_merge($promotion->toArray(), [
                    'is_active' => true,
                    'name' => $promoConfig['name'],
                ]),
                'player' => $player->fresh(),
                'message' => 'Started ' . $promoConfig['name'] . ' for "' . $song->title . '"',
            ],
        ], 201);
    }

    public function complete(Request $request, Promotion $promotion): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if ($promotion->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This promotion does not belong to you.',
            ], 403);
        }

        if ($promotion->isCompleted()) {
            return response()->json([
                'success' => false,
                'code' => 'ALREADY_COMPLETED',
                'message' => 'This promotion has already been completed.',
            ], 400);
        }

        if (!$promotion->isReadyToComplete()) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_READY',
                'message' => 'Promotion is still in progress.',
            ], 400);
        }

        // Award rewards
        $player->addFans($promotion->fan_reward);
        $player->addMoney($promotion->money_reward);
        $player->addReputation($promotion->reputation_reward);
        $player->addExperience(15);

        // Update group popularity
        $group = $promotion->group;
        $group->increment('popularity', (int) ($promotion->fan_reward * 0.1));

        // Mark completed
        $promotion->update(['completed_at' => now()]);

        $viralMessage = $promotion->went_viral ? ' 🔥 IT WENT VIRAL!' : '';

        return response()->json([
            'success' => true,
            'data' => [
                'promotion' => $promotion->fresh(),
                'rewards' => [
                    'fans' => $promotion->fan_reward,
                    'money' => $promotion->money_reward,
                    'reputation' => $promotion->reputation_reward,
                    'went_viral' => $promotion->went_viral,
                ],
                'player' => $player->fresh(),
                'message' => 'Promotion completed!' . $viralMessage,
            ],
        ]);
    }
}

