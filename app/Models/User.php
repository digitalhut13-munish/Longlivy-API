<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function emailVerificationCodes(): HasMany
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function streaks(): HasMany
    {
        return $this->hasMany(UserStreak::class);
    }

    public function fastings(): HasMany
    {
        return $this->hasMany(Fasting::class);
    }

    public function weightLogs(): HasMany
    {
        return $this->hasMany(WeightLog::class);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function meditationSessions(): HasMany
    {
        return $this->hasMany(MeditationSession::class);
    }

    public function meditationReminders(): HasMany
    {
        return $this->hasMany(MeditationReminder::class);
    }

    public function meditationTemplates(): HasMany
    {
        return $this->hasMany(MeditationTemplate::class);
    }

    public function fastingPlans(): HasMany
    {
        return $this->hasMany(FastingPlan::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }
}
