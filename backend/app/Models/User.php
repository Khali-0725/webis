<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'barangay_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    // Role helpers
    public function isClient(): bool { return $this->role === UserRole::Client; }
    public function isProvider(): bool { return $this->role === UserRole::Provider; }
    public function isAdmin(): bool { return $this->role === UserRole::Admin; }
    public function isActive(): bool { return $this->status === UserStatus::Active; }

    // Accessors
    public function getFullNameAttribute(): string { return trim($this->first_name.' '.$this->last_name); }
    public function getInitialsAttribute(): string {
        $first = mb_substr($this->first_name ?? '', 0, 1);
        $last = mb_substr($this->last_name ?? '', 0, 1);
        return mb_strtoupper($first.$last);
    }

    // Relationships
    public function barangay(): BelongsTo { return $this->belongsTo(Barangay::class); }
    public function providerProfile(): HasOne { return $this->hasOne(ProviderProfile::class); }
    public function clientBookings(): HasMany { return $this->hasMany(Booking::class, 'client_id'); }
    public function providerBookings(): HasManyThrough { return $this->hasManyThrough(Booking::class, ProviderProfile::class); }
    public function clientConversations(): HasMany { return $this->hasMany(Conversation::class, 'client_id'); }
    public function providerConversations(): HasMany { return $this->hasMany(Conversation::class, 'provider_user_id'); }
    public function writtenReviews(): HasMany { return $this->hasMany(Review::class, 'client_id'); }
    public function filedReports(): HasMany { return $this->hasMany(Report::class, 'reporter_id'); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class, 'actor_id'); }
    public function clientPayments(): HasMany { return $this->hasMany(Payment::class, 'client_id'); }

    /**
     * Points the reset link at the React SPA rather than a Blade route.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
