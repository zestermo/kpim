<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agency_name',
        'money',
        'fans',
        'reputation',
        'level',
        'experience',
        'manager_id',
    ];

    protected $casts = [
        'money' => 'integer',
        'fans' => 'integer',
        'reputation' => 'integer',
        'level' => 'integer',
        'experience' => 'integer',
    ];

    // Starting values
    public const STARTING_MONEY = 50000;
    public const STARTING_FANS = 0;
    public const STARTING_REPUTATION = 0;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function idols(): HasMany
    {
        return $this->hasMany(Idol::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function addMoney(int $amount): void
    {
        $this->increment('money', $amount);
    }

    public function spendMoney(int $amount): bool
    {
        if ($this->money < $amount) {
            return false;
        }
        $this->decrement('money', $amount);
        return true;
    }

    public function addFans(int $amount): void
    {
        $this->increment('fans', $amount);
    }

    public function addReputation(int $amount): void
    {
        $this->increment('reputation', $amount);
    }

    public function addExperience(int $amount): void
    {
        $this->increment('experience', $amount);
        $this->checkLevelUp();
    }

    protected function checkLevelUp(): void
    {
        $requiredExp = $this->level * 100;
        while ($this->experience >= $requiredExp) {
            $this->experience -= $requiredExp;
            $this->level++;
            $requiredExp = $this->level * 100;
        }
        $this->save();
    }

    public function getManagerBonus(string $type): float
    {
        if (!$this->manager) {
            return 0;
        }
        
        if ($this->manager->bonus_type === $type) {
            return $this->manager->bonus_value;
        }
        
        return 0;
    }
}

