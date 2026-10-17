<?php

declare(strict_types=1);

namespace App\Media\Metadata;

use App\Utilities\Types;
use JamesHeinrich\GetID3\Utils;

final class Id3v2Text
{
    /**
     * Tags getID3 can only write as a single frame without description
     */
    public const array DESCRIPTIONLESS_FRAMES = [
        'comment' => ['COMM', 'COM'],
        'url_user' => ['WXXX', 'WXX'],
    ];

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

    /**
     * Reads the text of the first frame without description for each DESCRIPTIONLESS_FRAMES tag.
     * Uses the raw frames since getID3's tags don't show which value has no description.
     *
     * @param array<array-key, mixed> $info getID3 analysis result
     *
     * @return array<key-of<self::DESCRIPTIONLESS_FRAMES>, string>
     */
    public static function getDescriptionlessFrameTexts(array $info): array
    {
        $frames = Types::array($info['id3v2'] ?? []);
        $texts = [];

        foreach (self::DESCRIPTIONLESS_FRAMES as $tag => $frameNames) {
            $text = self::findDescriptionlessFrameText($frames, $frameNames);
            if ($text !== null) {
                $texts[$tag] = $text;
            }
        }

        return $texts;
    }

    /**
     * @param array<array-key, mixed> $frames
     * @param list<string> $frameNames
     */
    private static function findDescriptionlessFrameText(array $frames, array $frameNames): ?string
    {
        foreach ($frameNames as $frameName) {
            foreach (Types::array($frames[$frameName] ?? []) as $frame) {
                $frame = Types::array($frame);
                if (Types::string($frame['description'] ?? null) !== '') {
                    continue;
                }

                // The link of URL frames is always ISO-8859-1
                $text = isset($frame['url'])
                    ? self::decode('ISO-8859-1', Types::string($frame['url']))
                    : self::decode(
                        Types::string($frame['encoding'] ?? null, 'ISO-8859-1'),
                        Types::string($frame['data'] ?? null)
                    );

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }
}
