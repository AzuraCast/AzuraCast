<?php

declare(strict_types=1);

namespace Unit;

use App\Event\Media\WriteMetadata;
use App\Media\Enums\MetadataTags;
use App\Media\Metadata;
use App\Media\Metadata\Writer;
use Codeception\Test\Unit;
use JamesHeinrich\GetID3\GetID3;

final class MediaMetadataWriterTest extends Unit
{
    private Writer $writer;

    private string $path;

    protected function _before(): void
    {
        $this->writer = new Writer();

        $this->path = tempnam(sys_get_temp_dir(), 'azuracast_media_') . '.mp3';
        file_put_contents($this->path, self::buildSilentMp3());
    }

    protected function _after(): void
    {
        @unlink($this->path);
    }

    public function testExtraTagsDoNotPreventKnownTagsFromBeingWritten(): void
    {
        $this->write(
            [
                MetadataTags::Title->value => 'Test Title',
                MetadataTags::Artist->value => 'Test Artist',
            ],
            [
                'amplify' => null,
                'cross_start_next' => null,
                'cue_in' => 1.5,
                'cue_out' => null,
                'fade_in' => null,
                'fade_out' => 2.5,
            ]
        );

        $tags = $this->readTags();

        self::assertSame(['Test Title'], $tags['title'] ?? null);
        self::assertSame(['Test Artist'], $tags['artist'] ?? null);
    }

    public function testExtraTagsAreWrittenAsIndividualTxxxFramesWhenFilled(): void
    {
        $this->write(
            [MetadataTags::Title->value => 'Test Title'],
            [
                'amplify' => null,
                'cross_start_next' => null,
                'cue_in' => 1.5,
                'cue_out' => null,
                'fade_in' => null,
                'fade_out' => 2.5,
            ]
        );

        self::assertSame(
            [
                'cue_in' => '1.5',
                'fade_out' => '2.5',
            ],
            $this->readTags()['text'] ?? null
        );
    }

    public function testEmptyExtraTagsWriteNoTxxxFrames(): void
    {
        $this->write(
            [MetadataTags::Title->value => 'Test Title'],
            [
                'amplify' => null,
                'cross_start_next' => null,
                'cue_in' => null,
                'cue_out' => null,
                'fade_in' => null,
                'fade_out' => null,
            ]
        );

        $tags = $this->readTags();

        self::assertSame(['Test Title'], $tags['title'] ?? null);
        self::assertArrayNotHasKey('text', $tags);
    }

    /**
     * @param array<value-of<MetadataTags>, mixed> $knownTags
     * @param array<string, mixed> $extraTags
     */
    private function write(array $knownTags, array $extraTags): void
    {
        $metadata = new Metadata();
        $metadata->setKnownTags($knownTags);
        $metadata->setExtraTags($extraTags);

        ($this->writer)(new WriteMetadata($metadata, $this->path));
    }

    /**
     * @return array<string, mixed>
     */
    private function readTags(): array
    {
        $info = (new GetID3())->analyze($this->path);

        return $info['tags']['id3v2'] ?? [];
    }

    /**
     * A handful of silent MPEG-1 Layer III frames, enough for GetID3 to recognise the file as
     * an MP3 and to allow ID3v2 tags to be written to it.
     */
    private static function buildSilentMp3(): string
    {
        // FF FB: sync word, MPEG-1, Layer III, no CRC.
        // 90:    128 kbit/s, 44.1 kHz, no padding.
        // 04:    stereo, original.
        $frameHeader = "\xFF\xFB\x90\x04";

        // floor(144 * 128000 / 44100) = 417 bytes per frame, header included.
        $frame = $frameHeader . str_repeat("\x00", 413);

        return str_repeat($frame, 100);
    }
}
