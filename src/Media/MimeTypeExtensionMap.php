<?php

declare(strict_types=1);

namespace App\Media;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class MimeTypeExtensionMap extends GeneratedExtensionToMimeTypeMap
{
    public const ADDED_MIME_TYPES = [
        'stm' => 'audio/x-mod',
    ];

    public function lookupMimeType(string $extension): ?string
    {
        return self::MIME_TYPES_FOR_EXTENSIONS[$extension]
            ?? self::ADDED_MIME_TYPES[$extension]
            ?? null;
    }
}
