<?php

namespace App\Services;

use App\Models\Setting;

class RegistrationTokenService
{
    public const SETTING_KEY = 'asesi_registration_token';

    /**
     * Get the currently active registration token for asesi.
     * Generates a new random one if not yet initialized.
     */
    public static function getCurrentToken(): string
    {
        $token = Setting::get(self::SETTING_KEY);

        if (empty($token) || ! self::isValidFormat((string) $token)) {
            $token = self::generateNewToken();
        }

        return (string) $token;
    }

    /**
     * Generate and store a new weekly registration token (proindo + 4 random digits).
     * Example: proindo1234, proindo7865
     */
    public static function generateNewToken(): string
    {
        $digits = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $token = 'proindo'.$digits;

        Setting::set(
            self::SETTING_KEY,
            $token,
            'Token pendaftaran mandiri mingguan untuk asesi baru'
        );

        return $token;
    }

    /**
     * Validate an inputted token against the active registration token.
     */
    public static function validateToken(?string $inputToken): bool
    {
        if (empty($inputToken)) {
            return false;
        }

        return strcasecmp(trim($inputToken), trim(self::getCurrentToken())) === 0;
    }

    /**
     * Check if a token string has the expected format (proindo followed by 4 digits).
     */
    public static function isValidFormat(string $token): bool
    {
        return (bool) preg_match('/^proindo[0-9]{4}$/i', trim($token));
    }
}
