<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

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
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'privacy_accepted_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * @return HasMany<TourView, $this>
     */
    public function tourViews(): HasMany
    {
        return $this->hasMany(TourView::class);
    }

    /**
     * Whether the user already saw the guided tour on the given device.
     */
    public function hasSeenTour(string $tour, string $deviceId): bool
    {
        return $this->tourViews()
            ->where('tour', $tour)
            ->where('device_id', $deviceId)
            ->exists();
    }

    /**
     * @return HasOne<Patient, $this>
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * @return HasOne<Doctor, $this>
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Determine whether the user has any of the given roles.
     */
    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Roles this account may be switched to. Doctor and patient accounts own
     * clinical history, so their role is fixed; administration roles can swap.
     *
     * @return list<UserRole>
     */
    public function assignableRoles(?User $editor = null): array
    {
        if ($editor?->is($this) || ! in_array($this->role, [UserRole::Admin, UserRole::Receptionist], true)) {
            return [$this->role];
        }

        return [UserRole::Admin, UserRole::Receptionist];
    }

    /**
     * Determine whether the user belongs to the clinic personnel.
     */
    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }
}
