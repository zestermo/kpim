<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'type',
        'level',
    ];

    public const TYPE_PROMO_PAYOUT = 'promo_payout';
    public const TYPE_VIRALITY = 'virality';
    public const TYPE_PRODUCTION_SPEED = 'production_speed';

    /**
     * Static config for upgrades.
     */
    public const CONFIG = [
        self::TYPE_PROMO_PAYOUT => [
            'label' => 'Promotion Payout',
            'description' => 'Increase fans, money, and reputation earned from promotions.',
            'base_cost_fans' => 800,
            'base_cost_reputation' => 5,
            'scaling' => 1.6,
            'bonus_per_level' => 0.05, // +5% per level
            'max_level' => 10,
        ],
        self::TYPE_VIRALITY => [
            'label' => 'Virality Chance',
            'description' => 'Boost chance for promotions to go viral.',
            'base_cost_fans' => 1200,
            'base_cost_reputation' => 12,
            'scaling' => 1.55,
            'bonus_per_level' => 0.01, // +1% virality per level
            'max_level' => 10,
        ],
        self::TYPE_PRODUCTION_SPEED => [
            'label' => 'Production Speed',
            'description' => 'Reduce song production time.',
            'base_cost_fans' => 1000,
            'base_cost_reputation' => 8,
            'scaling' => 1.5,
            'bonus_per_level' => 0.05, // -5% time per level
            'max_level' => 8,
        ],
    ];

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public static function costForNextLevel(string $type, int $currentLevel): array
    {
        $config = self::CONFIG[$type];
        $multiplier = pow($config['scaling'], $currentLevel);

        return [
            'fans' => (int) round($config['base_cost_fans'] * $multiplier),
            'reputation' => (int) round($config['base_cost_reputation'] * $multiplier),
        ];
    }

    public static function getCatalogWithProgress(PlayerProfile $player): array
    {
        return collect(self::CONFIG)->map(function ($config, $type) use ($player) {
            $level = $player->getUpgradeLevel($type);
            $maxLevel = $config['max_level'];
            $nextCost = $level >= $maxLevel ? null : self::costForNextLevel($type, $level);
            $currentBonus = $level * $config['bonus_per_level'];

            return [
                'type' => $type,
                'label' => $config['label'],
                'description' => $config['description'],
                'level' => $level,
                'max_level' => $maxLevel,
                'bonus_per_level' => $config['bonus_per_level'],
                'current_bonus' => $currentBonus,
                'next_cost' => $nextCost,
            ];
        })->values()->toArray();
    }
}

