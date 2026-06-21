<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\BuildingType;
use App\Enums\ResourceType;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property int $treasury
 * @property float $wage_share
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 */
#[Fillable(['name', 'slug', 'is_personal'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * The Tiles this Team owns (ADR-0002).
     *
     * @return HasMany<Tile, $this>
     */
    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class);
    }

    /**
     * The Team's Resource totals (ADR-0002 — Resources accrue to the Team).
     *
     * @return HasMany<TeamResource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(TeamResource::class);
    }

    /**
     * Add to a Resource total, creating the running total if absent. The
     * increment itself is a single atomic statement (ADR-0010).
     */
    public function addResource(ResourceType $type, int $amount): void
    {
        $this->resources()->firstOrCreate(['type' => $type]);
        $this->resources()->where('type', $type)->increment('amount', $amount);
    }

    /**
     * The Team's current total of a Resource type.
     */
    public function resourceTotal(ResourceType $type): int
    {
        return (int) $this->resources()->where('type', $type)->value('amount');
    }

    /**
     * Remove from a Resource total only if enough is in stock. Returns whether
     * the removal happened — the conditional decrement is a single atomic
     * statement, so a stockpile can never go negative under concurrency
     * (ADR-0010).
     */
    public function removeResource(ResourceType $type, int $amount): bool
    {
        return $this->resources()
            ->where('type', $type)
            ->where('amount', '>=', $amount)
            ->decrement('amount', $amount) > 0;
    }

    /**
     * Credit the treasury (atomic increment).
     */
    public function depositTreasury(int $amount): void
    {
        $this->newQuery()->whereKey($this->getKey())->increment('treasury', $amount);
    }

    /**
     * Debit the treasury only if it can afford it. Returns whether the debit
     * happened — the conditional decrement is a single atomic statement, so the
     * treasury can never go negative under concurrency (ADR-0010).
     */
    public function withdrawTreasury(int $amount): bool
    {
        return $this->newQuery()
            ->whereKey($this->getKey())
            ->where('treasury', '>=', $amount)
            ->decrement('treasury', $amount) > 0;
    }

    /**
     * The wage share, clamped to the system floor/cap (ADR-0006). Defensive: the
     * setter already clamps, but config bounds may shift over a World's life.
     */
    public function clampedWageShare(): float
    {
        $floor = (float) config('money.wage_share_floor');
        $cap = (float) config('money.wage_share_cap');

        return max($floor, min($cap, (float) $this->wage_share));
    }

    /**
     * The Building types this Team has unlocked via the tech tree (ADR-0003).
     *
     * @return HasMany<TeamUnlockedBuilding, $this>
     */
    public function unlockedBuildings(): HasMany
    {
        return $this->hasMany(TeamUnlockedBuilding::class);
    }

    /**
     * Whether this Team may place the given Building type yet.
     */
    public function hasUnlocked(BuildingType $type): bool
    {
        return $this->unlockedBuildings()->where('building_type', $type)->exists();
    }

    /**
     * Unlock a Building type for this Team (idempotent).
     */
    public function unlockBuilding(BuildingType $type): void
    {
        $this->unlockedBuildings()->firstOrCreate(['building_type' => $type]);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'treasury' => 'integer',
            'wage_share' => 'float',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
