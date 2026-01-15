<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalLoginCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function createForEmail(string $email): self
    {
        // Delete old codes for this email
        self::where('email', $email)->delete();

        return self::create([
            'email' => $email,
            'code' => self::generateCode(),
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public static function verify(string $email, string $code): ?self
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function countRecentAttempts(string $email): int
    {
        return self::where('email', $email)
            ->where('created_at', '>', now()->subHour())
            ->count();
    }
}
