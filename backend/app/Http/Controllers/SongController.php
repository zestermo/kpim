<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SongController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $songs = $request->user()->playerProfile->songs()
            ->with('group')
            ->get()
            ->map(function ($song) {
                $song->checkCompletion();
                return array_merge($song->toArray(), [
                    'is_completed' => $song->isCompleted(),
                    'is_in_production' => $song->isInProduction(),
                    'promotion_power' => $song->promotion_power,
                ]);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'songs' => $songs,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'title' => 'nullable|string|max:255',
            'genre' => 'required|in:' . implode(',', Song::GENRES),
            'debug_complete' => 'sometimes|boolean',
        ]);

        // Verify group ownership
        $group = Group::find($validated['group_id']);
        if ($group->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This group does not belong to you.',
            ], 403);
        }

        // Check cost
        if ($player->money < Song::BASE_PRODUCTION_COST) {
            return response()->json([
                'success' => false,
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Not enough money to produce a song. Cost: ' . Song::BASE_PRODUCTION_COST,
            ], 400);
        }

        $player->spendMoney(Song::BASE_PRODUCTION_COST);

        // Generate song quality based on group
        $baseQuality = rand(40, 80);
        $quality = Song::calculateQuality($group, $baseQuality);
        $hype = rand(30, 70);

        $audioUrl = $this->randomAudio();

        $speedMultiplier = $player->getProductionSpeedMultiplier();
        $productionMinutes = max(1, (int) ceil(Song::BASE_PRODUCTION_MINUTES * $speedMultiplier));

        $song = Song::create([
            'player_profile_id' => $player->id,
            'group_id' => $group->id,
            'title' => $validated['title'] ?? Song::generateTitle(),
            'genre' => $validated['genre'],
            'audio_url' => $audioUrl,
            'quality' => $quality,
            'hype' => $hype,
            'production_cost' => Song::BASE_PRODUCTION_COST,
            'production_ends_at' => now()->addMinutes($productionMinutes),
        ]);

        // Debug: instantly complete song if requested
        if (!empty($validated['debug_complete'])) {
            $song->update([
                'completed_at' => now(),
                'production_ends_at' => now(),
            ]);
        }

        // Add experience
        $player->addExperience(25);

        return response()->json([
            'success' => true,
            'data' => [
                'song' => array_merge($song->toArray(), [
                    'is_in_production' => empty($validated['debug_complete']),
                    'is_completed' => !empty($validated['debug_complete']) || $song->isCompleted(),
                    'production_power' => $song->promotion_power,
                ]),
                'player' => $player->fresh(),
                'message' => 'Started producing "' . $song->title . '"',
            ],
        ], 201);
    }

    public function show(Request $request, Song $song): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if ($song->player_profile_id !== $player->id) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_OWNER',
                'message' => 'This song does not belong to you.',
            ], 403);
        }

        $song->checkCompletion();
        $song->load(['group', 'promotions']);

        return response()->json([
            'success' => true,
            'data' => [
                'song' => array_merge($song->toArray(), [
                    'is_completed' => $song->isCompleted(),
                    'is_in_production' => $song->isInProduction(),
                    'promotion_power' => $song->promotion_power,
                ]),
            ],
        ]);
    }

    private function randomAudio(): ?string
    {
        $roots = [
            public_path('music/songs'),                     // backend public
            base_path('../frontend/public/music/songs'),    // frontend assets (sibling directory)
        ];

        $files = collect($roots)
            ->filter(fn($path) => File::exists($path))
            ->flatMap(function ($path) {
                return collect(File::files($path))
                    ->filter(fn($f) => in_array(strtolower($f->getExtension()), ['mp3', 'ogg', 'wav']));
            });

        if ($files->isEmpty()) {
            return $this->fallbackAudio();
        }

        $file = $files->random();
        Log::info('Assigning audio to song', [
            'picked' => $file->getFilename(),
            'root' => dirname($file->getPathname()),
        ]);
        return '/music/songs/' . $file->getFilename();
    }

    private function fallbackAudio(): ?string
    {
        // Use lobby/menu track if no song assets exist
        return File::exists(public_path('music/menu.mp3')) ? '/music/menu.mp3' : null;
    }
}

