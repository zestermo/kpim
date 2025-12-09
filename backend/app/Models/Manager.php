<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bonus_type',
        'bonus_value',
        'flavor_text',
        'sprite_key',
    ];

    protected $casts = [
        'bonus_value' => 'float',
    ];

    // Bonus types
    public const BONUS_PROMOTION = 'promotion_boost';
    public const BONUS_TRAINING = 'training_speed';
    public const BONUS_VIRALITY = 'virality_chance';
    public const BONUS_AWARD = 'award_chance';
    public const BONUS_SCOUTING = 'scouting_quality';

    public static function getDefaultManagers(): array
    {
        return [
            [
                'name' => 'Marble Mall',
                'bonus_type' => self::BONUS_PROMOTION,
                'bonus_value' => 0.15,
                'flavor_text' => 'PR mastermind who can make any news cycle sparkle.',
                'sprite_key' => 'manager_marble',
            ],
            [
                'name' => 'Nela Space',
                'bonus_type' => self::BONUS_TRAINING,
                'bonus_value' => 0.20,
                'flavor_text' => 'Relentless coach who squeezes 20% more out of every practice.',
                'sprite_key' => 'manager_nela',
            ],
            [
                'name' => 'Spach Murmen',
                'bonus_type' => self::BONUS_VIRALITY,
                'bonus_value' => 0.10,
                'flavor_text' => 'Social sorcerer with an eye for viral moments.',
                'sprite_key' => 'manager_spach',
            ],
            [
                'name' => 'Harris Suppick',
                'bonus_type' => self::BONUS_SCOUTING,
                'bonus_value' => 0.12,
                'flavor_text' => 'Talent bloodhound who finds hidden gems with ease.',
                'sprite_key' => 'manager_harris',
            ],
        ];
    }
}

