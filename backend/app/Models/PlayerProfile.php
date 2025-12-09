<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AgencyUpgrade;

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

    public function agencyUpgrades(): HasMany
    {
        return $this->hasMany(AgencyUpgrade::class);
    }

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

    public function getUpgradeLevel(string $type): int
    {
        return (int) ($this->agencyUpgrades()->where('type', $type)->value('level') ?? 0);
    }

    public function getUpgradeBonus(string $type): float
    {
        $config = AgencyUpgrade::CONFIG[$type] ?? null;
        if (!$config) {
            return 0;
        }

        return $this->getUpgradeLevel($type) * $config['bonus_per_level'];
    }

    public function getPromotionPayoutBonus(): float
    {
        return $this->getUpgradeBonus(AgencyUpgrade::TYPE_PROMO_PAYOUT);
    }

    public function getViralityBonus(): float
    {
        return $this->getUpgradeBonus(AgencyUpgrade::TYPE_VIRALITY);
    }

    public function getProductionSpeedMultiplier(): float
    {
        // Multiplier applied to production time. Capped so it never hits zero.
        $reduction = $this->getUpgradeBonus(AgencyUpgrade::TYPE_PRODUCTION_SPEED);
        return max(0.4, 1 - $reduction);
    }
}

