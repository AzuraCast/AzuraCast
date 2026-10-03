<?php

declare(strict_types=1);

namespace App\Media\Metadata;

use JamesHeinrich\GetID3\Utils;

final class Id3v2Text
{
    /**
     * Decodes ID3v2 frame text to UTF-8 via getID3 while handling
     * its quirk to return raw input on falsy decoded text
     */
    public static function decode(string $encoding, string $data): string
    {
        $decoded = Utils::iconv_fallback($encoding, 'UTF-8', $data);

        return ($data !== '' && $decoded === $data && str_starts_with($encoding, 'UTF-16'))
            ? '0'
            : $decoded;
    }
}
