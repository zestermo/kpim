<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Idol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()->playerProfile->groups()
            ->with('members')
            ->get()
            ->map(function ($group) {
                return array_merge($group->toArray(), [
                    'average_star_power' => $group->average_star_power,
                    'total_star_power' => $group->total_star_power,
                    'member_count' => $group->member_count,
                ]);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'concept' => 'required|in:' . implode(',', Group::CONCEPTS),
            'member_ids' => 'required|array|min:' . Group::MIN_MEMBERS . '|max:' . Group::MAX_MEMBERS,
            'member_ids.*' => 'exists:idols,id',
        ]);

        // Verify all idols belong to player and are not in other groups
        $idols = Idol::whereIn('id', $validated['member_ids'])
            ->where('player_profile_id', $player->id)
            ->get();

        if ($idols->count() !== count($validated['member_ids'])) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_MEMBERS',
                'message' => 'Some idols do not belong to you.',
            ], 400);
        }

        // Check for idols already in groups
        foreach ($idols as $idol) {
            if ($idol->isInGroup()) {
                return response()->json([
                    'success' => false,
                    'code' => 'IDOL_IN_GROUP',
                    'message' => $idol->name . ' is already in another group.',
                ], 400);
            }
        }

        // Check cost
        if ($player->money < Group::CREATION_COST) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money to create a group. Cost: ' . Group::CREATION_COST,
            ], 400);
        }

        $player->spendMoney(Group::CREATION_COST);

        // Create group
        $group = Group::create([
            'player_profile_id' => $player->id,
            'name' => $validated['name'],
            'concept' => $validated['concept'],
            'debut_date' => now(),
        ]);

        // Add members
        $group->members()->attach($validated['member_ids']);

        // Add experience
        $player->addExperience(50);

        return response()->json([
            'success' => true,
            'data' => [
                'group' => $group->load('members'),
                'player' => $player->fresh(),
                'message' => $group->name . ' has debuted!',
            ],
        ], 201);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if ($group->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This group does not belong to you.',
            ], 403);
        }

        $group->load(['members', 'songs', 'promotions']);

        return response()->json([
            'success' => true,
            'data' => [
                'group' => array_merge($group->toArray(), [
                    'average_star_power' => $group->average_star_power,
                    'total_star_power' => $group->total_star_power,
                    'average_vocal' => $group->average_vocal,
                    'average_dance' => $group->average_dance,
                    'average_visual' => $group->average_visual,
                ]),
            ],
        ]);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if ($group->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This group does not belong to you.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'concept' => 'sometimes|in:' . implode(',', Group::CONCEPTS),
        ]);

        $group->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'group' => $group->fresh()->load('members'),
                'message' => 'Group updated successfully.',
            ],
        ]);
    }

    public function addMember(Request $request, Group $group): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if ($group->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This group does not belong to you.',
            ], 403);
        }

        if (!$group->canAddMember()) {
            return response()->json([
                'success' => false,
                'code' => 'GROUP_FULL',
                'message' => 'Group has reached maximum members (' . Group::MAX_MEMBERS . ').',
            ], 400);
        }

        $validated = $request->validate([
            'idol_id' => 'required|exists:idols,id',
            'position' => 'nullable|string|max:50',
        ]);

        $idol = Idol::find($validated['idol_id']);

        if ($idol->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This idol does not belong to you.',
            ], 403);
        }

        if ($idol->isInGroup()) {
            return response()->json([
                'success' => false,
                'code' => 'IDOL_IN_GROUP',
                'message' => $idol->name . ' is already in another group.',
            ], 400);
        }

        $group->members()->attach($idol->id, [
            'position' => $validated['position'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'group' => $group->fresh()->load('members'),
                'message' => $idol->name . ' joined ' . $group->name,
            ],
        ]);
    }

    public function removeMember(Request $request, Group $group, Idol $idol): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if ($group->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This group does not belong to you.',
            ], 403);
        }

        if ($group->members()->count() <= Group::MIN_MEMBERS) {
            return response()->json([
                'success' => false,
                'code' => 'MIN_MEMBERS',
                'message' => 'Group must have at least ' . Group::MIN_MEMBERS . ' members.',
            ], 400);
        }

        $group->members()->detach($idol->id);

        return response()->json([
            'success' => true,
            'data' => [
                'group' => $group->fresh()->load('members'),
                'message' => $idol->name . ' left ' . $group->name,
            ],
        ]);
    }
}

