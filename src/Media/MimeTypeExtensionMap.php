<?php

declare(strict_types=1);

namespace App\Media;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

final class MimeTypeExtensionMap extends GeneratedExtensionToMimeTypeMap
{
    public const ADDED_MIME_TYPES = [
        'stm' => 'audio/x-mod',
    ];

    public function lookupMimeType(string $extension): ?string
    {
        return parent::lookupMimeType($extension)
            ?? self::ADDED_MIME_TYPES[$extension]
            ?? null;
    }
}
