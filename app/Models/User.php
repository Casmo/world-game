<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Exceptions\PlayerBusyException;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property int $energy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, Activity> $activities
 */
#[Fillable(['name', 'email', 'password', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The maximum (and default) Energy a player can hold.
     */
    public const MAX_ENERGY = 100;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'energy' => 'integer',
        ];
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * The player's currently in-progress Activity, if any.
     */
    public function activeActivity(): ?Activity
    {
        return $this->activities()
            ->where('status', ActivityStatus::Active)
            ->latest('id')
            ->first();
    }

    /**
     * Begin an Activity. A player may only perform one Activity at a time.
     *
     * @throws PlayerBusyException
     */
    public function startActivity(ActivityType $type): Activity
    {
        if ($this->activeActivity() !== null) {
            throw new PlayerBusyException;
        }

        return $this->activities()->create([
            'type' => $type,
            'status' => ActivityStatus::Active,
            'started_at' => now(),
            'completes_at' => now()->addSeconds($type->durationSeconds()),
        ]);
    }

    /**
     * Current Energy, computed on read from the stored base plus any in-progress
     * Sleep (which restores Energy proportionally to time slept). Never fatal.
     */
    public function currentEnergy(): int
    {
        $energy = (int) $this->energy;

        $sleep = $this->activeActivity();
        if ($sleep?->type === ActivityType::Sleep) {
            $duration = ActivityType::Sleep->durationSeconds();
            $elapsed = (int) abs($sleep->started_at->diffInSeconds(now()));
            $fraction = $duration > 0 ? min(1.0, $elapsed / $duration) : 1.0;
            $energy += (int) floor((self::MAX_ENERGY - $energy) * $fraction);
        }

        return max(0, min(self::MAX_ENERGY, $energy));
    }

    /**
     * Persist Energy at its maximum (called when a Sleep activity completes).
     */
    public function restoreEnergyToFull(): void
    {
        $this->forceFill(['energy' => self::MAX_ENERGY])->save();
    }
}
