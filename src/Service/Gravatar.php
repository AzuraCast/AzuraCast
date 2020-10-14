<?php

/**
 * Gravatar - Globally Recognized Avatars Connector
 */

namespace App\Service;

class Gravatar
{
    public static function get($email, $size = 50, $default = 'mm'): string
    {
        $grav_prefix = 'https://www.gravatar.com';

        $url_params = [
            'd' => $default,
            'r' => 'g',
            'size' => $size,
        ];
        $grav_url = $grav_prefix . '/avatar/' . md5(strtolower($email)) . '?' . http_build_query($url_params);

        return htmlspecialchars($grav_url);
    }
}
