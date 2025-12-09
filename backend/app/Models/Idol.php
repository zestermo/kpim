<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Idol extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'name',
        'vocal',
        'dance',
        'visual',
        'charm',
        'stamina',
        'popularity',
        'training_until',
        'sprite_key',
        'rarity',
    ];

    protected $casts = [
        'vocal' => 'integer',
        'dance' => 'integer',
        'visual' => 'integer',
        'charm' => 'integer',
        'stamina' => 'integer',
        'popularity' => 'integer',
        'training_until' => 'datetime',
    ];

    // Rarity tiers
    public const RARITY_COMMON = 'common';
    public const RARITY_UNCOMMON = 'uncommon';
    public const RARITY_RARE = 'rare';
    public const RARITY_EPIC = 'epic';
    public const RARITY_LEGENDARY = 'legendary';

    // Stat ranges by rarity
    public const STAT_RANGES = [
        self::RARITY_COMMON => ['min' => 20, 'max' => 45],
        self::RARITY_UNCOMMON => ['min' => 35, 'max' => 60],
        self::RARITY_RARE => ['min' => 50, 'max' => 75],
        self::RARITY_EPIC => ['min' => 65, 'max' => 88],
        self::RARITY_LEGENDARY => ['min' => 80, 'max' => 100],
    ];

    // Scouting costs
    public const SCOUT_COST = 1000;
    public const PACK_COST = 2500;

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function getStarPowerAttribute(): int
    {
        // Weighted sum of stats
        return (int) (
            ($this->vocal * 0.25) +
            ($this->dance * 0.25) +
            ($this->visual * 0.20) +
            ($this->charm * 0.20) +
            ($this->stamina * 0.10)
        );
    }

    public function isTraining(): bool
    {
        return $this->training_until && $this->training_until->isFuture();
    }

    public function isInGroup(): bool
    {
        return $this->groups()->exists();
    }

    public static function generateRandom(string $rarity = null, float $qualityBonus = 0): array
    {
        $rarity = $rarity ?? self::rollRarity();
        $range = self::STAT_RANGES[$rarity];
        
        $min = $range['min'];
        $max = min(100, (int) ($range['max'] * (1 + $qualityBonus)));

        $names = self::getRandomNames();
        
        return [
            'name' => $names[array_rand($names)],
            'vocal' => rand($min, $max),
            'dance' => rand($min, $max),
            'visual' => rand($min, $max),
            'charm' => rand($min, $max),
            'stamina' => rand($min, $max),
            'popularity' => 0,
            'rarity' => $rarity,
            // Bind sprite to one of the idol images (1-12) for consistency
            'sprite_key' => 'idol_' . rand(1, 12),
        ];
    }

    public static function rollRarity(): string
    {
        $roll = rand(1, 100);
        
        if ($roll <= 50) return self::RARITY_COMMON;
        if ($roll <= 80) return self::RARITY_UNCOMMON;
        if ($roll <= 94) return self::RARITY_RARE;
        if ($roll <= 99) return self::RARITY_EPIC;
        return self::RARITY_LEGENDARY;
    }

    public static function getRandomNames(): array
    {
        return [
            'Minho', 'Taehyun', 'Woojin', 'Hyunjin', 'Seungmin', 'Changbin', 'Bangchan', 'Heeseung',
            'Jake', 'Jay', 'Sunghoon', 'Jungwon', 'Sunoo', 'Ni-ki', 'Yeonjun', 'Soobin', 'Beomgyu',
            'Huening Kai', 'Felix', 'Yechan', 'Jisung', 'Jongho', 'San', 'Yeosang', 'Wooyoung',
            'Mingi', 'Hongjoong', 'Yunho', 'Seonghwa', 'Jinyoung', 'Mark', 'Jaehyun', 'Taeyong',
            'Doyoung', 'Ten', 'Renjun', 'Jeno', 'Haechan', 'Jaemin', 'Chenle', 'Shotaro', 'Sungchan',
            'Lucas', 'Kihyun', 'Changkyun', 'Shownu', 'Minhyuk', 'Hyungwon', 'I.M', 'Hyuk',
            'Ravi', 'N', 'Leo', 'Ken', 'Hongseok', 'Shinwon', 'Yuto', 'Yanan', 'Yeo One',
            'Yanan', 'Hui', 'Kino', 'Wooseok', 'Jungwoo', 'Taemin', 'Jongin', 'Joon', 'Seok',
        ];
    }
}

