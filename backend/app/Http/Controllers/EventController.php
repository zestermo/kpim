<?php

namespace App\Http\Controllers;

use App\Models\PlayerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    private const MAX_EVENTS_PER_PULSE = 2;
    private const LOG_LIMIT = 25;

    private const EVENT_TYPES = [
        'instagram_post' => [
            'money' => [200, 400],
            'fans' => [50, 120],
            'template' => '📸 {idol} posted a viral photo!',
        ],
        'livestream' => [
            'money' => [500, 900],
            'fans' => [150, 300],
            'template' => '🎥 {idol} went live and wowed fans!',
        ],
        'fansign' => [
            'money' => [800, 1200],
            'fans' => [250, 400],
            'template' => '✍️ {idol} hosted a fansign event!',
        ],
        'solo_release' => [
            'money' => [1200, 2000],
            'fans' => [400, 650],
            'template' => '🎵 {idol} dropped a solo track!',
        ],
        'pop_up_busking' => [
            'money' => [300, 700],
            'fans' => [120, 220],
            'template' => '🎤 {idol} did a pop-up busking show!',
        ],
        'behind_the_scenes' => [
            'money' => [150, 350],
            'fans' => [60, 140],
            'template' => '📹 {idol} shared behind-the-scenes moments!',
        ],
    ];

    public function pulse(Request $request): JsonResponse
    {
        /** @var PlayerProfile $player */
        $player = $request->user()->playerProfile()->with('idols')->first();

        if (!$player || $player->idols->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'events' => [],
                    'player' => $player,
                ],
            ]);
        }

        $events = $this->generateEvents($player);

        // Apply rewards
        $totalMoney = 0;
        $totalFans = 0;
        foreach ($events as $event) {
            $totalMoney += $event['money'];
            $totalFans += $event['fans'];
        }

        if ($totalMoney > 0) {
            $player->addMoney($totalMoney);
        }
        if ($totalFans > 0) {
            $player->addFans($totalFans);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events,
                'player' => $player->fresh(),
            ],
        ]);
    }

    private function generateEvents(PlayerProfile $player): array
    {
        $idols = $player->idols;
        $idolCount = $idols->count();

        // Chance scales with idol count
        $potentialEvents = min(self::MAX_EVENTS_PER_PULSE, max(1, (int) ceil($idolCount / 3)));
        $numEvents = rand(0, $potentialEvents);

        $events = [];
        $types = array_keys(self::EVENT_TYPES);

        for ($i = 0; $i < $numEvents; $i++) {
            $idol = $idols->random();
            $type = $types[array_rand($types)];
            $config = self::EVENT_TYPES[$type];

            $money = rand($config['money'][0], $config['money'][1]);
            $fans = rand($config['fans'][0], $config['fans'][1]);

            $message = str_replace('{idol}', $idol->name, $config['template']);

            $events[] = [
                'type' => $type,
                'idol_id' => $idol->id,
                'idol_name' => $idol->name,
                'money' => $money,
                'fans' => $fans,
                'message' => $message,
                'timestamp' => Carbon::now()->toIso8601String(),
            ];
        }

        return array_slice($events, 0, self::LOG_LIMIT);
    }
}

