<?php

declare(strict_types=1);

namespace App\Utilities;

use JsonException;
use RuntimeException;

final class Json
{
    public static function loadFromFile(
        string $path,
        bool $throwOnError = true
    ): array {
        if (is_file($path)) {
            $fileContents = file_get_contents($path);
            if (false !== $fileContents) {
                try {
                    return (array)json_decode($fileContents, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    if ($throwOnError) {
                        throw new RuntimeException(
                            sprintf(
                                'Could not parse JSON at "%s": %s',
                                $path,
                                $e->getMessage()
                            )
                        );
                    }
                }
            } elseif ($throwOnError) {
                throw new RuntimeException(sprintf('Error reading file: "%s"', $path));
            }
        } elseif ($throwOnError) {
            throw new RuntimeException(sprintf('File not found: "%s"', $path));
        }

        return [];
    }
}
