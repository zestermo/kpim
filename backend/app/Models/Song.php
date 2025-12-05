<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'group_id',
        'title',
        'genre',
        'quality',
        'hype',
        'production_cost',
        'completed_at',
        'production_ends_at',
    ];

    protected $casts = [
        'quality' => 'integer',
        'hype' => 'integer',
        'production_cost' => 'integer',
        'completed_at' => 'datetime',
        'production_ends_at' => 'datetime',
    ];

    // Genres
    public const GENRE_POP = 'pop';
    public const GENRE_DANCE = 'dance';
    public const GENRE_BALLAD = 'ballad';
    public const GENRE_HIPHOP = 'hiphop';
    public const GENRE_RNB = 'rnb';
    public const GENRE_EDM = 'edm';
    public const GENRE_ROCK = 'rock';

    public const GENRES = [
        self::GENRE_POP,
        self::GENRE_DANCE,
        self::GENRE_BALLAD,
        self::GENRE_HIPHOP,
        self::GENRE_RNB,
        self::GENRE_EDM,
        self::GENRE_ROCK,
    ];

    // Production settings
    public const BASE_PRODUCTION_COST = 8000;
    public const BASE_PRODUCTION_MINUTES = 5; // 5 minutes for MVP testing

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isInProduction(): bool
    {
        return $this->production_ends_at && 
               $this->production_ends_at->isFuture() && 
               !$this->isCompleted();
    }

    public function checkCompletion(): bool
    {
        if ($this->isCompleted()) {
            return true;
        }

        if ($this->production_ends_at && $this->production_ends_at->isPast()) {
            $this->completed_at = now();
            $this->save();
            return true;
        }

        return false;
    }

    public function getPromotionPowerAttribute(): int
    {
        // Song's power for promotions
        $base = $this->quality + $this->hype;
        $groupBonus = $this->group ? $this->group->average_star_power : 0;
        
        return (int) ($base + ($groupBonus * 0.5));
    }

    public static function generateTitle(): string
    {
        $prefixes = ['Love', 'Star', 'Dream', 'Fire', 'Ice', 'Night', 'Day', 'Moon', 'Sun', 'Heart'];
        $suffixes = ['Story', 'Light', 'Dance', 'Kiss', 'Beat', 'Fever', 'Rush', 'Game', 'Way', 'Time'];
        
        return $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)];
    }

    public static function calculateQuality(Group $group, int $baseQuality): int
    {
        $groupBonus = $group->calculateSongQualityBonus();
        return min(100, (int) ($baseQuality * (1 + $groupBonus)));
    }
}

