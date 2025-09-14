<?php

declare(strict_types=1);

namespace App\Media;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

final class MimeTypeExtensionMap extends GeneratedExtensionToMimeTypeMap
{
    public const array ADDED_MIME_TYPES = [
        'mod' => 'audio/x-mod',
        'stm' => 'audio/x-mod',
        // Temporary Bugfix for incorrectly mapped aac mime type in library
        // @see https://github.com/thephpleague/mime-type-detection/pull/40
        'aac' => 'audio/aac',
    ];

    public function lookupMimeType(string $extension): ?string
    {
        return self::ADDED_MIME_TYPES[$extension]
            ?? parent::lookupMimeType($extension)
            ?? null;
    }
}
