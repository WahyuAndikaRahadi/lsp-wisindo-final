<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class RegistrationTokenService
{
    public const SETTING_KEY = 'asesi_registration_token';

    public const PREVIOUS_SETTING_KEY = 'asesi_registration_token_previous';

    public const ROTATION_DAYS = 7;

    /**
     * Get the currently active registration token for asesi.
     * Generates a new random one if not yet initialized OR if more than 7 days have passed.
     */
    public static function getCurrentToken(): string
    {
        $setting = Setting::where('key', self::SETTING_KEY)->first();

        // 1. If not initialized or invalid format, generate fresh
        if (! $setting || empty($setting->value) || ! self::isValidFormat((string) $setting->value)) {
            return self::generateNewToken();
        }

        // 2. Self-healing lazy rotation: check if token has exceeded the 7-day rotation period
        if ($setting->updated_at && $setting->updated_at->copy()->addDays(self::ROTATION_DAYS)->isPast()) {
            return self::generateNewToken();
        }

        return (string) $setting->value;
    }

    /**
     * Generate and store a new weekly registration token (proindo + 4 random digits).
     * Example: proindo1234, proindo7865
     * Automatically preserves current token as previous token for grace period.
     */
    public static function generateNewToken(): string
    {
        $currentSetting = Setting::where('key', self::SETTING_KEY)->first();
        if ($currentSetting && ! empty($currentSetting->value) && self::isValidFormat((string) $currentSetting->value)) {
            Setting::set(
                self::PREVIOUS_SETTING_KEY,
                $currentSetting->value,
                'Token pendaftaran periode sebelumnya (grace period)'
            );
        }

        $digits = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $token = 'wisindo'.$digits;

        Setting::set(
            self::SETTING_KEY,
            $token,
            'Token pendaftaran mandiri mingguan untuk asesi baru'
        );

        return $token;
    }

    /**
     * Validate an inputted token against the active registration token or previous token.
     */
    public static function validateToken(?string $inputToken): bool
    {
        if (empty($inputToken)) {
            return false;
        }

        $input = trim($inputToken);

        // 1. Current token (auto-rotates if >= 7 days)
        if (strcasecmp($input, trim(self::getCurrentToken())) === 0) {
            return true;
        }

        // 2. Previous token (grace period for candidates given token right before weekly rotation)
        $previousToken = Setting::get(self::PREVIOUS_SETTING_KEY);
        if (! empty($previousToken) && strcasecmp($input, trim($previousToken)) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if a token string has the expected format (wisindo or proindo followed by 4 digits).
     */
    public static function isValidFormat(string $token): bool
    {
        return (bool) preg_match('/^(wisindo|proindo)[0-9]{4}$/i', trim($token));
    }

    /**
     * Get metadata about current token for admin/asesor display.
     *
     * @return array{token: string, updated_at: Carbon, expires_at: Carbon, days_remaining: int}
     */
    public static function getTokenInfo(): array
    {
        $token = self::getCurrentToken();
        $setting = Setting::where('key', self::SETTING_KEY)->first();

        $updatedAt = $setting?->updated_at ? Carbon::parse($setting->updated_at) : now();
        $expiresAt = $updatedAt->copy()->addDays(self::ROTATION_DAYS);
        $daysRemaining = max(0, (int) ceil(now()->diffInSeconds($expiresAt, false) / 86400));

        return [
            'token' => $token,
            'updated_at' => $updatedAt,
            'expires_at' => $expiresAt,
            'days_remaining' => $daysRemaining,
        ];
    }
}
