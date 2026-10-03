<?php

declare(strict_types=1);

namespace App\Media\Metadata;

use App\Event\Media\WriteMetadata;
use App\Media\Enums\MetadataTags;
use App\Media\MetadataInterface;
use App\Utilities\Types;
use JamesHeinrich\GetID3\GetID3;
use JamesHeinrich\GetID3\Utils;
use JamesHeinrich\GetID3\Write\ID3v2;
use JamesHeinrich\GetID3\WriteTags;
use RuntimeException;

/**
 * Writes the managed tags into a media file and keeps the file's other tags where getID3 allows it.
 * Embedded pictures stay untouched unless the metadata sets or removes the artwork.
 *
 * Restrictions from getID3:
 * - Existing ID3v2 frames other than text, URL, TXXX, UFID, APIC and the first COMM/WXXX are dropped
 * - ID3v2 pictures with an identical description field are reduced to the first one
 * - FLAC pictures can only be added, never replaced or removed
 */
final class Writer
{
    private const array MANAGED_TAGS = [
        MetadataTags::Title->value,
        MetadataTags::Artist->value,
        MetadataTags::Album->value,
        MetadataTags::Genre->value,
        MetadataTags::UnsynchronisedLyric->value,
        MetadataTags::Isrc->value,
    ];

    private const array VORBIS_PICTURE_TAGS = [
        'picture',
        'coverartmime',
    ];

    private const array ID3V2_PLAIN_VALUE_TAGS = [
        MetadataTags::Comment->value,
        MetadataTags::UnsynchronisedLyric->value,
    ];

    private const string MIME_PATTERN = '#^[^/\x00]+/[^/\x00]+$#';

    private const int PICTURE_TYPE_COVER_FRONT = 3;
    private const int PICTURE_TYPE_MIN = 0;
    private const int PICTURE_TYPE_MAX = 20;

    private const int UFID_MAX_LENGTH = 64;

    private const float REPLAYGAIN_DEFAULT_REFERENCE_LOUDNESS = 89.0;

    public function __invoke(WriteMetadata $event): void
    {
        $path = $event->getPath();
        $metadata = $event->getMetadata();

        $pathExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $tagFormats = match ($pathExt) {
            'mp3', 'mp2' => ['id3v1', 'id3v2.3'],
            'flac' => ['metaflac'],
            'ogg' => ['vorbiscomment'],
            default => throw new RuntimeException('Cannot write tag formats based on file type.'),
        };

        $info = (new GetID3())->analyze($path);

        if (!empty($info['error'])) {
            throw new RuntimeException(
                'Cannot read existing tags: ' . implode(', ', Types::array($info['error']))
            );
        }

        $tagData = in_array($pathExt, ['flac', 'ogg'], true)
            ? $this->buildVorbisTagData($metadata, $info, $pathExt === 'flac')
            : $this->buildId3v2TagData($metadata, $info);

        $tagwriter = new WriteTags();
        $tagwriter->filename = $path;
        $tagwriter->tagformats = $tagFormats;
        $tagwriter->tag_data = $tagData;
        $tagwriter->overwrite_tags = true;
        $tagwriter->remove_other_tags = false;
        $tagwriter->tag_encoding = 'UTF-8';
        $tagwriter->WriteTags();

        if (!empty($tagwriter->errors) || !empty($tagwriter->warnings)) {
            $messages = array_merge($tagwriter->errors, $tagwriter->warnings);

            throw new RuntimeException(implode(', ', $messages));
        }
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<string, mixed>
     */
    private function buildId3v2TagData(MetadataInterface $metadata, array $info): array
    {
        $existing = self::getInfoSection($info, 'tags', 'id3v2');
        if ($existing === []) {
            // Fallback for ID3v1 files that would otherwise lose their values
            $existing = self::getInfoSection($info, 'tags', 'id3v1');
        }

        $knownTags = $metadata->getKnownTags();

        /** @var array<string, list<string>> $textFrames */
        $textFrames = [];

        // getID3 only rewrites text (T***) & URL (W***) frames from plain values
        // The managed tag, TXXX & WXXX are added separately afterwards.
        foreach ($existing as $key => $values) {
            $key = (string) $key;
            $frameName = ID3v2::ID3v2ShortFrameNameLookup(3, $key);

            if (
                in_array($key, self::MANAGED_TAGS, true)
                || isset($knownTags[$key])
                || in_array($frameName, ['', 'TXXX', 'WXXX'], true)
                || !in_array($frameName[0], ['T', 'W'], true)
            ) {
                continue;
            }

            $strings = array_values(array_map('strval', array_filter(Types::array($values), 'is_scalar')));
            if ($strings !== []) {
                $textFrames[$key] = $strings;
            }
        }

        $totalTracksEntries = Types::array($existing['totaltracks'] ?? []);
        $totalTracks = Types::stringOrNull($totalTracksEntries[0] ?? null, true);
        if ($totalTracks !== null && isset($textFrames['track_number'][0])) {
            $textFrames['track_number'] = [$textFrames['track_number'][0] . '/' . $totalTracks];
        }

        // getID3 writes these frames without a description, so only one of each can exist
        foreach (['comment', 'url_user'] as $key) {
            if (isset($knownTags[$key])) {
                continue;
            }

            $firstValue = Types::array($existing[$key] ?? []);
            $firstValue = reset($firstValue);
            if (is_string($firstValue) && $firstValue !== '') {
                $textFrames[$key] = [$firstValue];
            }
        }

        /** @var array<string, string> $knownTagsAsTxxx */
        $knownTagsAsTxxx = [];

        // Known tags without a plain ID3v2.3 frame would make getID3 abort the whole write
        foreach ($knownTags as $key => $value) {
            if (self::supportsId3v2PlainValue($key)) {
                $textFrames[$key] = [(string) $value];
            } else {
                $knownTagsAsTxxx[$key] = (string) $value;
            }
        }

        $tagData = $textFrames;

        $txxxRows = $this->buildTxxxRows($metadata, $info, $knownTagsAsTxxx);
        if ($txxxRows !== []) {
            $tagData['text'] = $txxxRows;
        }

        $ufid = Types::array(self::getInfoSection($info, 'id3v2', 'UFID')[0] ?? []);
        if (
            isset($ufid['ownerid'], $ufid['data'])
            && is_string($ufid['data'])
            && strlen($ufid['data']) <= self::UFID_MAX_LENGTH
        ) {
            $tagData['unique_file_identifier'] = [
                'ownerid' => $ufid['ownerid'],
                'data' => $ufid['data'],
            ];
        }

        $artwork = $metadata->getArtwork();
        if ($artwork !== null) {
            $tagData['attached_picture'] = [self::buildAttachedPicture($artwork)];
        } elseif (!$metadata->shouldRemoveArtwork()) {
            $pictures = self::buildExistingAttachedPictures($info);
            if ($pictures !== []) {
                $tagData['attached_picture'] = $pictures;
            }
        }

        return $tagData;
    }

    /**
     * @param array<string, mixed> $info
     * @param array<string, string> $knownTagsAsTxxx
     *
     * @return list<array{
     *  encodingid: int,
     *  description: string,
     *  data: string
     * }>
     */
    private function buildTxxxRows(MetadataInterface $metadata, array $info, array $knownTagsAsTxxx): array
    {
        $managedDescriptions = array_map(
            static fn(int|string $key): string => strtolower((string) $key),
            [
                ...array_keys($metadata->getExtraTags()),
                ...array_keys($knownTagsAsTxxx),
            ]
        );

        /** @var array<string, string> $values */
        $values = [];

        foreach (self::getInfoSection($info, 'id3v2', 'TXXX') as $frame) {
            $frame = Types::array($frame);
            $description = Types::string($frame['description'] ?? null);

            if (
                isset($values[$description])
                || in_array(strtolower($description), $managedDescriptions, true)
            ) {
                continue;
            }

            $values[$description] = Id3v2Text::decode(
                Types::string($frame['encoding'] ?? null, 'ISO-8859-1'),
                Types::string($frame['data'] ?? null)
            );
        }

        foreach (array_replace($this->getExtraTagValues($metadata), $knownTagsAsTxxx) as $key => $value) {
            $values[(string) $key] = $value;
        }

        $rows = [];
        foreach ($values as $description => $data) {
            $rows[] = self::encodeTxxxRow((string) $description, $data);
        }

        return $rows;
    }

    private static function supportsId3v2PlainValue(string $key): bool
    {
        if (in_array($key, self::ID3V2_PLAIN_VALUE_TAGS, true)) {
            return true;
        }

        $frameName = ID3v2::ID3v2ShortFrameNameLookup(
            majorversion: 3,
            long_description: $key
        );

        return $frameName !== 'TXXX' && str_starts_with($frameName, 'T');
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return list<array{
     *  encodingid: int,
     *  description: string,
     *  data: string,
     *  picturetypeid: int,
     *  mime: string
     * }>
     */
    private static function buildExistingAttachedPictures(array $info): array
    {
        $rows = [];

        foreach (['APIC', 'PIC'] as $frameName) {
            foreach (self::getInfoSection($info, 'id3v2', $frameName) as $frame) {
                $frame = Types::array($frame);

                $data = Types::string($frame['data'] ?? null);
                $mime = Types::string($frame['mime'] ?? null);
                if (!preg_match(self::MIME_PATTERN, $mime)) {
                    $mime = Types::string($frame['image_mime'] ?? null);
                }
                $pictureType = Types::int($frame['picturetypeid'] ?? null, self::PICTURE_TYPE_COVER_FRONT);

                if (
                    $data === ''
                    || !preg_match(self::MIME_PATTERN, $mime)
                    || $pictureType < self::PICTURE_TYPE_MIN
                    || $pictureType > self::PICTURE_TYPE_MAX
                ) {
                    continue;
                }

                $description = Id3v2Text::decode(
                    Types::string($frame['encoding'] ?? null, 'ISO-8859-1'),
                    Types::string($frame['description'] ?? null)
                );
                $encodingId = self::pickEncodingId($description);
                $description = self::encodeFrameText($encodingId, $description);

                // getID3 refuses a second picture with the same description
                if (isset($rows[$description])) {
                    continue;
                }

                $rows[$description] = [
                    'encodingid' => $encodingId,
                    'description' => $description,
                    'data' => $data,
                    'picturetypeid' => $pictureType,
                    'mime' => $mime,
                ];
            }
        }

        return array_values($rows);
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<string, mixed>
     */
    private function buildVorbisTagData(MetadataInterface $metadata, array $info, bool $isFlac): array
    {
        $managedTags = array_map(
            static fn(int|string $key): string => strtolower((string) $key),
            [
                ...self::MANAGED_TAGS,
                ...array_keys($metadata->getExtraTags()),
                ...self::VORBIS_PICTURE_TAGS,
            ]
        );

        $tagData = [];

        foreach (self::getInfoSection($info, 'tags', 'vorbiscomment') as $key => $values) {
            $key = (string) $key;
            if (in_array(strtolower($key), $managedTags, true)) {
                continue;
            }

            $strings = array_values(array_filter(Types::array($values), 'is_string'));
            if ($strings !== []) {
                $tagData[$key] = $strings;
            }
        }

        // getID3 parses ReplayGain comments into numbers while reading, so they have to be rebuilt
        $replayGain = self::getInfoSection($info, 'replay_gain');
        $trackGain = Types::array($replayGain['track'] ?? []);
        $albumGain = Types::array($replayGain['album'] ?? []);

        // getID3 fills in the default reference loudness even when the file has no such comment.
        // Better to omit it than to force it on every file that didn't have any value set.
        $referenceLoudness = $replayGain['reference_volume'] ?? null;
        if (
            is_numeric($referenceLoudness)
            && (float) $referenceLoudness === self::REPLAYGAIN_DEFAULT_REFERENCE_LOUDNESS
        ) {
            $referenceLoudness = null;
        }

        $replayGainTags = [
            'replaygain_track_gain' => [$trackGain['adjustment'] ?? null, '%.2f dB'],
            'replaygain_track_peak' => [$trackGain['peak'] ?? null, '%.6f'],
            'replaygain_album_gain' => [$albumGain['adjustment'] ?? null, '%.2f dB'],
            'replaygain_album_peak' => [$albumGain['peak'] ?? null, '%.6f'],
            'replaygain_reference_loudness' => [$referenceLoudness, '%.1f dB'],
        ];

        foreach ($replayGainTags as $key => [$value, $format]) {
            if (is_numeric($value)) {
                $tagData[$key] = [sprintf($format, (float) $value)];
            }
        }

        foreach ($metadata->getKnownTags() as $key => $value) {
            $tagData[$key] = [(string) $value];
        }

        foreach ($this->getExtraTagValues($metadata) as $key => $value) {
            $tagData[$key] = [$value];
        }

        $artwork = $metadata->getArtwork();
        if ($artwork !== null) {
            if (!$isFlac) {
                [$width, $height] = getimagesizefromstring($artwork) ?: [0, 0];
                $tagData['metadata_block_picture'] = [
                    self::encodeFlacPictureBlock(
                        $artwork,
                        'image/jpeg',
                        'cover art',
                        self::PICTURE_TYPE_COVER_FRONT,
                        $width,
                        $height
                    ),
                ];
            } elseif (empty(self::getInfoSection($info, 'flac')['PICTURE'])) {
                // getID3 can only add FLAC pictures since its metaflac call doesn't remove PICTURE blocks
                $tagData['attached_picture'] = [self::buildAttachedPicture($artwork)];
            }
        } elseif (!$isFlac && !$metadata->shouldRemoveArtwork()) {
            $pictures = self::buildExistingVorbisPictures($info);
            if ($pictures !== []) {
                $tagData['metadata_block_picture'] = $pictures;
            }
        }

        return $tagData;
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return list<string>
     */
    private static function buildExistingVorbisPictures(array $info): array
    {
        $blocks = [];

        foreach (self::getInfoSection($info, 'comments', 'picture') as $picture) {
            $picture = Types::array($picture);

            $data = Types::string($picture['data'] ?? null);
            $mime = Types::string($picture['image_mime'] ?? null);
            if ($data === '' || !preg_match(self::MIME_PATTERN, $mime)) {
                continue;
            }

            $blocks[] = self::encodeFlacPictureBlock(
                $data,
                $mime,
                Types::string($picture['description'] ?? null),
                Types::int($picture['typeid'] ?? null, self::PICTURE_TYPE_COVER_FRONT),
                Types::int($picture['image_width'] ?? null),
                Types::int($picture['image_height'] ?? null),
                Types::int($picture['color_depth'] ?? null),
                Types::int($picture['colors_indexed'] ?? null)
            );
        }

        return $blocks;
    }

    /**
     * @return array<string, string>
     */
    private function getExtraTagValues(MetadataInterface $metadata): array
    {
        $values = [];

        foreach ($metadata->getExtraTags() as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $values[$key] = (string) $value;
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<array-key, mixed>
     */
    private static function getInfoSection(array $info, string ...$path): array
    {
        $section = $info;
        foreach ($path as $key) {
            $section = Types::array($section[$key] ?? []);
        }

        return $section;
    }

    /**
     * getID3 copies TXXX & APIC strings verbatim, so they are encoded here as ISO-8859-1 for ASCII & UTF-16 otherwise
     */
    private static function pickEncodingId(string ...$values): int
    {
        return preg_match('/[^\x00-\x7F]/', implode('', $values)) ? 1 : 0;
    }

    private static function encodeFrameText(int $encodingId, string $value): string
    {
        return $encodingId === 0
            ? $value
            : "\xFF\xFE" . Utils::iconv_fallback('UTF-8', 'UTF-16LE', $value);
    }

    /**
     * @return array{
     *  encodingid: int,
     *  description: string,
     *  data: string
     * }
     */
    private static function encodeTxxxRow(string $description, string $data): array
    {
        $encodingId = self::pickEncodingId($description, $data);

        return [
            'encodingid' => $encodingId,
            'description' => self::encodeFrameText($encodingId, $description),
            'data' => self::encodeFrameText($encodingId, $data),
        ];
    }

    /**
     * @return array{
     *  encodingid: int,
     *  description: string,
     *  data: string,
     *  picturetypeid: int,
     *  mime: string
     * }
     */
    private static function buildAttachedPicture(string $artwork): array
    {
        return [
            'encodingid' => 0, // ISO-8859-1; 3=UTF8 but only allowed in ID3v2.4
            'description' => 'cover art',
            'data' => $artwork,
            'picturetypeid' => self::PICTURE_TYPE_COVER_FRONT,
            'mime' => 'image/jpeg',
        ];
    }

    /**
     * Encodes a picture as the base64 FLAC picture block used by the METADATA_BLOCK_PICTURE comment.
     * getID3 has a reader for it but no writer so we need to do this manually.
     */
    private static function encodeFlacPictureBlock(
        string $data,
        string $mime,
        string $description,
        int $pictureType,
        int $width,
        int $height,
        int $colorDepth = 0,
        int $colorsIndexed = 0
    ): string {
        return base64_encode(
            pack('N', $pictureType)
            . pack('N', strlen($mime)) . $mime
            . pack('N', strlen($description)) . $description
            . pack('N4', $width, $height, $colorDepth, $colorsIndexed)
            . pack('N', strlen($data)) . $data
        );
    }
}
