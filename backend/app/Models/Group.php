<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_profile_id',
        'name',
        'concept',
        'popularity',
        'debut_date',
    ];

    protected $casts = [
        'popularity' => 'integer',
        'debut_date' => 'date',
    ];

    // Concepts affect performance in different promotion types
    public const CONCEPT_CUTE = 'cute';
    public const CONCEPT_GIRL_CRUSH = 'girl_crush';
    public const CONCEPT_ELEGANT = 'elegant';
    public const CONCEPT_FRESH = 'fresh';
    public const CONCEPT_POWERFUL = 'powerful';
    public const CONCEPT_DARK = 'dark';
    public const CONCEPT_RETRO = 'retro';

    public const CONCEPTS = [
        self::CONCEPT_CUTE,
        self::CONCEPT_GIRL_CRUSH,
        self::CONCEPT_ELEGANT,
        self::CONCEPT_FRESH,
        self::CONCEPT_POWERFUL,
        self::CONCEPT_DARK,
        self::CONCEPT_RETRO,
    ];

    public const MIN_MEMBERS = 2;
    public const MAX_MEMBERS = 7;
    public const CREATION_COST = 10000;

    public function playerProfile(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Idol::class, 'group_members')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function getAverageStarPowerAttribute(): float
    {
        $members = $this->members;
        if ($members->isEmpty()) {
            return 0;
        }

        $totalPower = $members->sum(fn($idol) => $idol->star_power);
        return round($totalPower / $members->count(), 2);
    }

    public function getTotalStarPowerAttribute(): int
    {
        return $this->members->sum(fn($idol) => $idol->star_power);
    }

    public function getAverageVocalAttribute(): float
    {
        return round($this->members->avg('vocal') ?? 0, 2);
    }

    public function getAverageDanceAttribute(): float
    {
        return round($this->members->avg('dance') ?? 0, 2);
    }

    public function getAverageVisualAttribute(): float
    {
        return round($this->members->avg('visual') ?? 0, 2);
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members->count();
    }

    public function canAddMember(): bool
    {
        return $this->members->count() < self::MAX_MEMBERS;
    }

    public function calculateSongQualityBonus(): float
    {
        $avgPower = $this->average_star_power;
        return $avgPower / 200; // Max 50% bonus at 100 avg power
    }
}

