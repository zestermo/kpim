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
                'name' => 'Minji Park',
                'bonus_type' => self::BONUS_PROMOTION,
                'bonus_value' => 0.15,
                'flavor_text' => 'Former PR director with connections everywhere. Promotions are 15% more effective.',
                'sprite_key' => 'manager_minji',
            ],
            [
                'name' => 'Seojun Kim',
                'bonus_type' => self::BONUS_TRAINING,
                'bonus_value' => 0.20,
                'flavor_text' => 'Dance legend from the golden era. Training takes 20% less time.',
                'sprite_key' => 'manager_seojun',
            ],
            [
                'name' => 'Yuna Choi',
                'bonus_type' => self::BONUS_VIRALITY,
                'bonus_value' => 0.10,
                'flavor_text' => 'Social media maven. +10% chance for content to go viral.',
                'sprite_key' => 'manager_yuna',
            ],
            [
                'name' => 'Dohwan Lee',
                'bonus_type' => self::BONUS_AWARD,
                'bonus_value' => 0.08,
                'flavor_text' => 'Awards show insider. +8% chance to win at ceremonies.',
                'sprite_key' => 'manager_dohwan',
            ],
            [
                'name' => 'Soyeon Hwang',
                'bonus_type' => self::BONUS_SCOUTING,
                'bonus_value' => 0.12,
                'flavor_text' => 'Eagle-eyed talent scout. Scouted idols have 12% higher base stats.',
                'sprite_key' => 'manager_soyeon',
            ],
        ];
    }
}

