<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'group_id',
        'song_id',
        'type',
        'cost',
        'fan_reward',
        'money_reward',
        'reputation_reward',
        'started_at',
        'ends_at',
        'completed_at',
        'went_viral',
    ];

    protected $casts = [
        'cost' => 'integer',
        'fan_reward' => 'integer',
        'money_reward' => 'integer',
        'reputation_reward' => 'integer',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'went_viral' => 'boolean',
    ];

    // Promotion types
    public const TYPE_SOCIAL_POST = 'social_post';
    public const TYPE_PRESS_INTERVIEW = 'press_interview';
    public const TYPE_TV_APPEARANCE = 'tv_appearance';
    public const TYPE_SHOWCASE = 'showcase';
    public const TYPE_FANSIGN = 'fansign';

    // Promotion configurations
    public const PROMOTIONS = [
        self::TYPE_SOCIAL_POST => [
            'name' => 'Social Media Post',
            'cost' => 500,
            'duration_minutes' => 1,
            'base_fans' => 50,
            'base_money' => 100,
            'base_reputation' => 5,
            'required_fans' => 0,
            'required_reputation' => 0,
            'viral_chance' => 0.10,
            'viral_multiplier' => 5,
        ],
        self::TYPE_PRESS_INTERVIEW => [
            'name' => 'Press Interview',
            'cost' => 2000,
            'duration_minutes' => 3,
            'base_fans' => 150,
            'base_money' => 500,
            'base_reputation' => 20,
            'required_fans' => 500,
            'required_reputation' => 5,
            'viral_chance' => 0.05,
            'viral_multiplier' => 3,
        ],
        self::TYPE_TV_APPEARANCE => [
            'name' => 'TV Appearance',
            'cost' => 5000,
            'duration_minutes' => 5,
            'base_fans' => 400,
            'base_money' => 2000,
            'base_reputation' => 50,
            'required_fans' => 2000,
            'required_reputation' => 20,
            'viral_chance' => 0.15,
            'viral_multiplier' => 4,
        ],
        self::TYPE_SHOWCASE => [
            'name' => 'Showcase Event',
            'cost' => 10000,
            'duration_minutes' => 10,
            'base_fans' => 800,
            'base_money' => 5000,
            'base_reputation' => 100,
            'required_fans' => 5000,
            'required_reputation' => 50,
            'viral_chance' => 0.20,
            'viral_multiplier' => 3,
        ],
        self::TYPE_FANSIGN => [
            'name' => 'Fansign Event',
            'cost' => 3000,
            'duration_minutes' => 5,
            'base_fans' => 300,
            'base_money' => 1500,
            'base_reputation' => 30,
            'required_fans' => 1000,
            'required_reputation' => 10,
            'viral_chance' => 0.08,
            'viral_multiplier' => 2,
        ],
    ];

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function isActive(): bool
    {
        return $this->started_at !== null && 
               $this->completed_at === null &&
               $this->ends_at->isFuture();
    }

    public function isReadyToComplete(): bool
    {
        return $this->started_at !== null && 
               $this->completed_at === null &&
               $this->ends_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public static function calculateRewards(
        string $type, 
        Group $group, 
        Song $song, 
        float $promotionBonus = 0,
        float $viralityBonus = 0
    ): array {
        $config = self::PROMOTIONS[$type];
        
        // Base multiplier from group and song
        $powerMultiplier = 1 + (($group->average_star_power + $song->promotion_power) / 200);
        
        // Apply promotion bonus from manager
        $promotionMultiplier = 1 + $promotionBonus;
        
        // Calculate base rewards
        $fans = (int) ($config['base_fans'] * $powerMultiplier * $promotionMultiplier);
        $money = (int) ($config['base_money'] * $powerMultiplier * $promotionMultiplier);
        $reputation = (int) ($config['base_reputation'] * $powerMultiplier * $promotionMultiplier);
        
        // Check for viral
        $viralChance = $config['viral_chance'] + $viralityBonus;
        $wentViral = (rand(1, 100) / 100) <= $viralChance;
        
        if ($wentViral) {
            $fans = (int) ($fans * $config['viral_multiplier']);
            $money = (int) ($money * $config['viral_multiplier']);
            $reputation = (int) ($reputation * $config['viral_multiplier']);
        }
        
        return [
            'fan_reward' => $fans,
            'money_reward' => $money,
            'reputation_reward' => $reputation,
            'went_viral' => $wentViral,
        ];
    }

    public static function getAvailableTypes(): array
    {
        return array_map(function ($type, $config) {
            return [
                'type' => $type,
                'name' => $config['name'],
                'cost' => $config['cost'],
                'duration_minutes' => $config['duration_minutes'],
                'base_fans' => $config['base_fans'],
                'base_money' => $config['base_money'],
                'base_reputation' => $config['base_reputation'],
                'required_fans' => $config['required_fans'] ?? 0,
                'required_reputation' => $config['required_reputation'] ?? 0,
            ];
        }, array_keys(self::PROMOTIONS), self::PROMOTIONS);
    }
}

