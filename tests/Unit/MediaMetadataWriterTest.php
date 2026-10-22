<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\CustomField;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StationMediaRepository;
use App\Entity\StationMedia;
use App\Entity\StationMediaCustomField;
use App\Entity\StorageLocation;
use App\Flysystem\Adapter\LocalFilesystemAdapter;
use App\Flysystem\RemoteFilesystem;
use App\Media\Metadata;
use App\Media\Metadata\Id3v2Text;
use App\Media\MetadataInterface;
use App\Media\MetadataManager;
use App\Service\PlaylistConfiguration\DummyMediaGenerator;
use App\Service\PlaylistConfiguration\Schema\MediaEntry;
use App\Tests\Module;
use App\Utilities\Types;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use FFMpeg\FFMpeg;
use JamesHeinrich\GetID3\GetID3;
use JamesHeinrich\GetID3\Write\ID3v2;
use JamesHeinrich\GetID3\WriteTags;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

use const ARRAY_FILTER_USE_KEY;

/**
 * Tests the "save media" path (StationMedia::toMetadata() -> MetadataManager -> Metadata\Writer)
 * against real files rendered by the DummyMediaGenerator to verify our metadata writing works.
 */
final class MediaMetadataWriterTest extends Unit
{
    private const string TEST_ARTIST = 'Test Artist';
    private const string TEST_TITLE = 'Test Title';
    private const string TEST_ALBUM = 'Test Album';

    private const string FOREIGN_TITLE = 'Original Title';
    private const string FOREIGN_PUBLISHER = 'Müller Records';
    private const string FOREIGN_DATE = '2019';
    private const string FOREIGN_TRACK_NUMBER = '7';
    private const string FOREIGN_TRACK_TOTAL = '12';
    private const string FOREIGN_TRACK = self::FOREIGN_TRACK_NUMBER . '/' . self::FOREIGN_TRACK_TOTAL;
    private const string FOREIGN_COMMENT = 'Ripped by Test';
    private const string FOREIGN_ARTISTS_TAG = 'Artists';
    private const string FOREIGN_ARTISTS = 'Müller & Söhne';
    private const string REPLAYGAIN_TRACK_GAIN = '-7.45 dB';
    private const string REPLAYGAIN_TRACK_PEAK = '1.031567';
    private const string MUSICBRAINZ_ALBUM_ID_TAG = 'MusicBrainz Album Id';
    private const string MUSICBRAINZ_ALBUM_ID = '0f3b1c2a-1111-2222-3333-444455556666';
    private const string MP3GAIN_UNDO = '-006,-006,N';
    private const string MP3GAIN_MINMAX = '104,178';
    private const string MP3GAIN_TRACK_GAIN = '-4.475000 dB';
    private const string DESCRIPTIONLESS_TXXX = 'no description';
    private const string ITUNES_ADVISORY_TAG = 'ITUNESADVISORY';
    private const string ITUNES_NORMALIZATION_TAG = 'iTunNORM';
    private const string ITUNES_NORMALIZATION = ' 0000044E 00000413 00002C8D 00002A0C';
    private const string DESCRIBED_COMMENT = 'Described comment';
    private const string FOREIGN_URL = 'https://example.com/artist';
    private const string DESCRIBED_URL_TAG = 'Discogs';
    private const string DESCRIBED_URL = 'https://example.com/release';
    private const string ASCII_PUBLISHER = 'ASCII Records';
    private const string FOREIGN_COMPOSER = 'Seeded Composer';
    private const string SECOND_FOREIGN_COMPOSER = 'Second Composer';
    private const string NON_ASCII_COMPOSER = 'Søren Müller';
    private const string CUSTOM_COMPOSER = 'Custom Composer';
    private const string FOREIGN_ALBUM_ARTIST = 'Seeded Album Artist';
    private const string CUSTOM_ALBUM_ARTIST = 'Custom Album Artist';
    private const string CUSTOM_COMMENT = 'Custom Comment';
    private const string FOREIGN_DESCRIPTION = 'Seeded Description';
    private const string FOREIGN_LYRICS = 'Seeded Lyrics';
    private const string CUSTOM_LYRICS = 'Custom Lyrics';
    private const string CUSTOM_TAG = 'Likes';
    private const string CUSTOM_TAG_VALUE = '42';
    private const string CUSTOM_MUSICBRAINZ_ALBUM_ID = '9a8b7c6d-1111-2222-3333-444455556666';
    private const string CUSTOM_YEAR = '2020';
    private const string FOREIGN_ENCODED_BY = 'Seeded Encoding Person';
    private const string CUSTOM_ENCODED_BY = 'Custom Encoding Person';
    private const string CUSTOM_ENCODER_SETTINGS = 'Custom Encoder 1.0';
    private const string CUSTOM_URL = 'https://example.com/custom';
    private const string EMAIL_URL = 'mailto:artist@example.com';
    private const string NON_ASCII_COPYRIGHT = '© 2020 Müller Records';
    private const string LINKED_PICTURE_MIME = '-->';
    private const string LINKED_PICTURE_URL = 'https://example.com/cover.jpg';

    /** Tags whose text frame only exists in ID3v2.4 */
    private const array ID3V24_ONLY_TEXT_TAGS = [
        'encoding_time' => ['2020-05-13T10:00'],
        'involved_people_list' => ['producer', 'Jane Doe', 'mix', 'Søren Müller'],
        'mood' => ['Happy', 'Calm'],
        'musician_credits_list' => ['guitar', 'John', 'drums', 'Paul'],
        'original_release_time' => ['1999-03-01'],
        'produced_notice' => ['2020 Müller Records'],
        'recording_time' => ['2020-05-12'],
        'release_time' => ['2020-06-01'],
        'set_subtitle' => ['Disc One'],
        'tagging_time' => ['2020-05-14T12:30:00'],
    ];

    private EntityManagerInterface $em;
    private DummyMediaGenerator $dummyMediaGenerator;
    private StationMediaRepository $mediaRepo;
    private MetadataManager $metadataManager;
    private FFMpeg $ffmpeg;

    private string $storagePath;

    private StorageLocation $storageLocation;

    /** @var StationMedia[] */
    private array $generatedMedia = [];

    /** @var CustomField[] */
    private array $customFields = [];

    protected function _inject(Module $testsModule): void
    {
        $this->em = $testsModule->em;

        $di = $testsModule->container;
        $this->dummyMediaGenerator = $di->get(DummyMediaGenerator::class);
        $this->mediaRepo = $di->get(StationMediaRepository::class);
        $this->metadataManager = $di->get(MetadataManager::class);

        $this->ffmpeg = FFMpeg::create();
    }

    protected function _before(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/azuracast_metadata_writer_' . bin2hex(random_bytes(6));
        (new Filesystem())->mkdir($this->storagePath);

        $this->storageLocation = new StorageLocation(
            StorageLocationTypes::StationMedia,
            StorageLocationAdapters::Local
        );
        $this->storageLocation->path = $this->storagePath;

        $this->em->persist($this->storageLocation);
        $this->em->flush();
    }

    protected function _after(): void
    {
        foreach ($this->generatedMedia as $media) {
            $this->em->remove($media);
        }
        $this->generatedMedia = [];

        foreach ($this->customFields as $customField) {
            $this->em->remove($customField);
        }
        $this->customFields = [];

        $this->em->remove($this->storageLocation);
        $this->em->flush();
        $this->em->clear();

        (new Filesystem())->remove($this->storagePath);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function formatProvider(): array
    {
        return [
            'mp3' => ['mp3'],
            'flac' => ['flac'],
            'ogg' => ['ogg'],
        ];
    }

    public function testKnownTagsAreWrittenWhenNoExtraMetadataIsSet(): void
    {
        $media = $this->generateMedia();

        // A media row without any cue/fade values still exports every extra metadata key, just with
        // null values, so the writer must cope with an extra tag set that is never empty.
        self::assertSame(
            [
                'amplify' => null,
                'cross_start_next' => null,
                'cue_in' => null,
                'cue_out' => null,
                'fade_in' => null,
                'fade_out' => null,
            ],
            $media->toMetadata()->getExtraTags()
        );

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);

        self::assertSame([self::TEST_TITLE], $tags['title'] ?? null);
        self::assertSame([self::TEST_ARTIST], $tags['artist'] ?? null);
        self::assertSame([self::TEST_ALBUM], $tags['album'] ?? null);
        self::assertArrayNotHasKey('text', $tags);
    }

    public function testExtraMetadataIsWrittenAsTxxxFramesAndReadBack(): void
    {
        $media = $this->generateMedia();
        $media->extra_metadata = [
            'cue_in' => 1.5,
            'fade_out' => 2.5,
        ];

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);

        self::assertSame([self::TEST_TITLE], $tags['title'] ?? null);
        self::assertSame(
            [
                'cue_in' => '1.5',
                'fade_out' => '2.5',
            ],
            $tags['text'] ?? null
        );

        $metadata = $this->readMetadata($media);

        self::assertSame(self::TEST_TITLE, $metadata->getKnownTags()['title'] ?? null);
        self::assertSame('1.5', $metadata->getExtraTags()['cue_in'] ?? null);
        self::assertSame('2.5', $metadata->getExtraTags()['fade_out'] ?? null);
    }

    #[DataProvider('formatProvider')]
    public function testForeignTagsSurviveSave(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->tagFile($media, $this->getForeignTags());

        $media->extra_metadata = ['cue_in' => 1.5];

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $metadata = $this->readMetadata($media);
        $knownTags = $metadata->getKnownTags();
        $extraTags = $metadata->getExtraTags();

        self::assertSame(self::TEST_TITLE, $knownTags['title'] ?? null);
        self::assertSame(self::TEST_ARTIST, $knownTags['artist'] ?? null);
        self::assertSame(self::FOREIGN_PUBLISHER, $knownTags['publisher'] ?? null);
        self::assertSame(self::FOREIGN_DATE, $knownTags['year'] ?? null);
        self::assertSame(self::FOREIGN_COMMENT, $knownTags['comment'] ?? null);

        if ($extension === 'mp3') {
            self::assertSame(self::FOREIGN_TRACK_NUMBER, $knownTags['track_number'] ?? null);
            self::assertSame(self::FOREIGN_TRACK_TOTAL, $extraTags['totaltracks'] ?? null);
        } else {
            self::assertSame(self::FOREIGN_TRACK, $knownTags['track_number'] ?? null);
        }
        self::assertSame(self::REPLAYGAIN_TRACK_GAIN, $extraTags['replaygain_track_gain'] ?? null);
        self::assertSame(self::REPLAYGAIN_TRACK_PEAK, $extraTags['replaygain_track_peak'] ?? null);
        self::assertSame(self::MUSICBRAINZ_ALBUM_ID, $extraTags[strtolower(self::MUSICBRAINZ_ALBUM_ID_TAG)] ?? null);
        self::assertSame('1.5', $extraTags['cue_in'] ?? null);
        self::assertArrayNotHasKey('text', $extraTags);

        if ($extension === 'mp3') {
            $text = Types::array($this->readFileTags($media)['text'] ?? []);
            self::assertArrayHasKey(self::MUSICBRAINZ_ALBUM_ID_TAG, $text);
        }
    }

    #[DataProvider('formatProvider')]
    public function testClearedExtraMetadataIsRemovedWhileForeignTagsStay(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->tagFile($media, [self::MUSICBRAINZ_ALBUM_ID_TAG => self::MUSICBRAINZ_ALBUM_ID]);

        $media->extra_metadata = [
            'cue_in' => 1.5,
            'cue_out' => 2.5,
        ];
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $media->extra_metadata = ['cue_out' => null];
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $extraTags = $this->readMetadata($media)->getExtraTags();

        self::assertSame('1.5', $extraTags['cue_in'] ?? null);
        self::assertArrayNotHasKey('cue_out', $extraTags);
        self::assertSame(self::MUSICBRAINZ_ALBUM_ID, $extraTags[strtolower(self::MUSICBRAINZ_ALBUM_ID_TAG)] ?? null);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function replaceablePictureFormatProvider(): array
    {
        return [
            'mp3' => ['mp3'],
            'ogg' => ['ogg'],
        ];
    }

    #[DataProvider('replaceablePictureFormatProvider')]
    public function testEmbeddedPictureIsReplacedAndRemoved(string $extension): void
    {
        $media = $this->generateMedia($extension);

        self::assertTrue($this->mediaRepo->updateAlbumArt($media, $this->createCoverImage('red')));
        self::assertCount(1, $this->readPictures($media));

        self::assertTrue($this->mediaRepo->updateAlbumArt($media, $this->createCoverImage('blue')));

        $pictures = $this->readPictures($media);
        self::assertCount(1, $pictures);
        self::assertSame(
            file_get_contents($this->getLocalPath(StationMedia::getArtPath($media->unique_id))),
            $pictures[0]['data'] ?? null
        );

        $media->mtime = 0;
        $this->mediaRepo->removeAlbumArt($media);
        self::assertCount(0, $this->readPictures($media));

        $this->em->refresh($media);
        self::assertGreaterThan(0, $media->mtime);

        $vorbisTags = Types::array($this->analyze($media)['tags']['vorbiscomment'] ?? []);
        self::assertArrayNotHasKey('attached_picture', $vorbisTags);
    }

    public function testFlacPictureIsOnlyAddedWhenTheFileHasNone(): void
    {
        $media = $this->generateMedia('flac');

        self::assertTrue($this->mediaRepo->updateAlbumArt($media, $this->createCoverImage('red')));
        self::assertCount(1, $this->readPictures($media));

        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertCount(1, $this->readPictures($media));

        self::assertTrue($this->mediaRepo->updateAlbumArt($media, $this->createCoverImage('blue')));
        self::assertCount(1, $this->readPictures($media));

        $this->mediaRepo->removeAlbumArt($media);
        self::assertCount(1, $this->readPictures($media));
    }

    public function testFailedWriteLeavesMediaUntouched(): void
    {
        $media = $this->generateMedia('wav');
        $mtimeBefore = $media->mtime;

        self::assertFalse($this->mediaRepo->writeToFile($media));
        self::assertSame($mtimeBefore, $media->mtime);
    }

    public function testTagsAreUploadedToRemoteStorage(): void
    {
        $media = $this->generateMedia();
        $media->title = 'Changed Title';

        $remoteFs = new RemoteFilesystem(new LocalFilesystemAdapter($this->storagePath));

        self::assertTrue($this->mediaRepo->writeToFile($media, $remoteFs));
        self::assertSame(['Changed Title'], $this->readFileTags($media)['title'] ?? null);
    }

    public function testStorageErrorLeavesMediaUntouched(): void
    {
        $media = $this->generateMedia();
        $mtimeBefore = $media->mtime;

        $emptyRemoteFs = new RemoteFilesystem(new LocalFilesystemAdapter("{$this->storagePath}/empty"));

        self::assertFalse($this->mediaRepo->writeToFile($media, $emptyRemoteFs));
        self::assertSame($mtimeBefore, $media->mtime);
    }

    public function testTagsGetId3CannotReadAreNotRewritten(): void
    {
        $media = $this->generateMedia();
        $path = $this->getLocalPath($media->path);

        // Bump the ID3v2 major version to one getID3 does not parse, so reading the tag fails.
        self::assertSame('ID3', file_get_contents($path, false, null, 0, 3));

        $handle = fopen($path, 'r+b');
        self::assertNotFalse($handle);
        fseek($handle, 3);
        fwrite($handle, "\x09");
        fclose($handle);

        $hashBefore = md5_file($path);
        $mtimeBefore = $media->mtime;

        self::assertFalse($this->mediaRepo->writeToFile($media));
        self::assertSame($hashBefore, md5_file($path));
        self::assertSame($mtimeBefore, $media->mtime);
    }

    #[DataProvider('formatProvider')]
    public function testRepeatedSavesLeaveTheFileUnchanged(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->tagFile($media, $this->getForeignTags());
        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));

        $media->extra_metadata = ['cue_in' => 1.5];

        self::assertTrue($this->mediaRepo->writeToFile($media));
        $hash = md5_file($this->getLocalPath($media->path));

        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertSame($hash, md5_file($this->getLocalPath($media->path)));
    }

    public function testApeTagSurvivesSave(): void
    {
        $media = $this->generateMedia();
        $this->seedTagsWithGetId3(
            $media,
            [
                'MP3GAIN_UNDO' => [self::MP3GAIN_UNDO],
                'MP3GAIN_MINMAX' => [self::MP3GAIN_MINMAX],
                'REPLAYGAIN_TRACK_GAIN' => [self::MP3GAIN_TRACK_GAIN],
            ],
            ['ape']
        );

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $apeItems = Types::array($this->analyze($media)['ape']['items'] ?? []);

        self::assertSame([self::MP3GAIN_UNDO], Types::array($apeItems['mp3gain_undo'] ?? [])['data'] ?? null);
        self::assertSame([self::MP3GAIN_MINMAX], Types::array($apeItems['mp3gain_minmax'] ?? [])['data'] ?? null);
        self::assertSame(
            [self::MP3GAIN_TRACK_GAIN],
            Types::array($apeItems['replaygain_track_gain'] ?? [])['data'] ?? null
        );
        self::assertSame([self::TEST_TITLE], $this->readFileTags($media)['title'] ?? null);
    }

    public function testId3v1OnlyTagsAreCarriedIntoId3v2(): void
    {
        $media = $this->generateMedia();
        $this->tagFile(
            $media,
            // Without an ID3v2 tag ffmpeg does not map generic keys, so these are addressed by frame id.
            [
                'TIT2' => self::FOREIGN_TITLE,
                'TDRC' => self::FOREIGN_DATE,
                'TRCK' => self::FOREIGN_TRACK_NUMBER,
                'comment' => self::FOREIGN_COMMENT,
            ],
            id3v1Only: true
        );
        self::assertArrayNotHasKey('id3v2', Types::array($this->analyze($media)['tags'] ?? []));

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);

        self::assertSame([self::TEST_TITLE], $tags['title'] ?? null);
        self::assertSame([self::FOREIGN_DATE], $tags['year'] ?? null);
        self::assertSame([self::FOREIGN_TRACK_NUMBER], $tags['track_number'] ?? null);
        self::assertSame([self::FOREIGN_COMMENT], $tags['comment'] ?? null);
    }

    public function testDescriptionlessTxxxFrameSurvivesSave(): void
    {
        $media = $this->generateMedia();
        $this->seedTagsWithGetId3(
            $media,
            ['text' => [['description' => '', 'data' => self::DESCRIPTIONLESS_TXXX]]],
            ['id3v2.3']
        );

        $media->extra_metadata = ['cue_in' => 1.5];

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $text = Types::array($this->readFileTags($media)['text'] ?? []);

        self::assertContains(self::DESCRIPTIONLESS_TXXX, $text);
        self::assertSame('1.5', $text['cue_in'] ?? null);
    }

    public function testTxxxFramesUseLatin1ForAsciiAndUtf16Otherwise(): void
    {
        $media = $this->generateMedia();
        $this->tagFile($media, [self::FOREIGN_ARTISTS_TAG => self::FOREIGN_ARTISTS]);

        $media->extra_metadata = ['cue_in' => 1.5];

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $encodings = [];
        foreach (Types::array($this->analyze($media)['id3v2']['TXXX'] ?? []) as $frame) {
            $frame = Types::array($frame);
            $encodings[Types::string($frame['description'] ?? null)] = $frame['encodingid'] ?? null;
        }

        self::assertSame(0, $encodings['cue_in'] ?? null);
        self::assertSame(1, $encodings[self::FOREIGN_ARTISTS_TAG] ?? null);

        $text = Types::array($this->readFileTags($media)['text'] ?? []);
        self::assertSame(self::FOREIGN_ARTISTS, $text[self::FOREIGN_ARTISTS_TAG] ?? null);
    }

    public function testUtf16TxxxFrameWithFalsyValueSurvivesSave(): void
    {
        $media = $this->generateMedia();
        // iTunes stores TXXX frames as UTF-16 with BOM even for ASCII, which ffmpeg never does
        $utf16Description = "\xFF\xFE" . mb_convert_encoding(self::ITUNES_ADVISORY_TAG, 'UTF-16LE', 'UTF-8');
        $this->seedTagsWithGetId3(
            $media,
            [
                'text' => [
                    [
                        'encodingid' => 1,
                        'description' => $utf16Description,
                        'data' => "\xFF\xFE0\x00",
                    ],
                ],
            ],
            ['id3v2.3']
        );

        $frame = $this->readTxxxFrames($media)[self::ITUNES_ADVISORY_TAG] ?? [];
        self::assertSame(1, $frame['encodingid'] ?? null);
        self::assertSame("\xFF\xFE0\x00", $frame['data'] ?? null);

        $media->extra_metadata = ['cue_in' => 1.5];

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $frame = $this->readTxxxFrames($media)[self::ITUNES_ADVISORY_TAG] ?? [];
        self::assertSame(0, $frame['encodingid'] ?? null);
        self::assertSame('0', $frame['data'] ?? null);
    }

    public function testUrlFrameAndAsciiTextFramesKeepLatin1Encoding(): void
    {
        $media = $this->generateMedia();
        $this->seedTagsWithGetId3(
            $media,
            [
                'url_artist' => [self::FOREIGN_URL],
                'publisher' => [self::ASCII_PUBLISHER],
            ],
            ['id3v2.3']
        );

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);

        self::assertSame([self::FOREIGN_URL], $tags['url_artist'] ?? null);
        self::assertSame([self::ASCII_PUBLISHER], $tags['publisher'] ?? null);

        $publisherFrame = Types::array(Types::array($this->analyze($media)['id3v2']['TPUB'] ?? [])[0] ?? []);
        self::assertSame(0, $publisherFrame['encodingid'] ?? null);
    }

    /**
     * Mp3tag & Picard store several values in one text frame, separated by null characters.
     *
     * @return array<string, array{int, string, list<string>}>
     */
    public static function multiValueTextFrameProvider(): array
    {
        $asciiComposers = [self::FOREIGN_COMPOSER, self::SECOND_FOREIGN_COMPOSER];
        $mixedComposers = [self::NON_ASCII_COMPOSER, self::FOREIGN_COMPOSER];

        return [
            'ISO-8859-1' => [0, implode(Id3v2Text::MULTI_VALUE_SEPARATOR, $asciiComposers), $asciiComposers],
            'UTF-16 with a BOM per value' => [
                1,
                implode("\x00\x00", array_map(
                    static fn(string $value): string => "\xFF\xFE" . mb_convert_encoding($value, 'UTF-16LE', 'UTF-8'),
                    $mixedComposers
                )),
                $mixedComposers,
            ],
        ];
    }

    /**
     * @param list<string> $values
     */
    #[DataProvider('multiValueTextFrameProvider')]
    public function testTextFrameWithSeveralValuesStaysOneFrame(int $encodingId, string $data, array $values): void
    {
        $media = $this->generateMedia();
        $this->seedId3v2Frames($media, ['TCOM' => [['encodingid' => $encodingId, 'data' => $data]]]);
        self::assertSame($values, $this->readFileTags($media)['composer'] ?? null);

        self::assertTrue($this->mediaRepo->writeToFile($media));
        $hash = md5_file($this->getLocalPath($media->path));

        self::assertCount(1, Types::array($this->analyze($media)['id3v2']['TCOM'] ?? []));
        self::assertSame($values, $this->readFileTags($media)['composer'] ?? null);

        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertSame($hash, md5_file($this->getLocalPath($media->path)));
    }

    /**
     * getID3 keys comments by description, but falls back to numeric keys for "0", numeric
     * and repeated descriptions, so these must not be mistaken for the visible comment.
     *
     * @return array<string, array{list<array<string, mixed>>, ?string}>
     */
    public static function commentFramesProvider(): array
    {
        // iTunes keeps its loudness data in a COMM frame with a description, often ahead of the visible comment
        $itunesFrame = self::commentFrame(self::ITUNES_NORMALIZATION_TAG, self::ITUNES_NORMALIZATION);
        $visibleFrame = self::commentFrame('', self::FOREIGN_COMMENT);

        return [
            'described frame ahead of the comment' => [
                [$itunesFrame, $visibleFrame],
                self::FOREIGN_COMMENT,
            ],
            'numeric description' => [
                [self::commentFrame('1', self::DESCRIBED_COMMENT), $visibleFrame],
                self::FOREIGN_COMMENT,
            ],
            'same description in another language' => [
                [
                    $itunesFrame,
                    self::commentFrame(self::ITUNES_NORMALIZATION_TAG, self::ITUNES_NORMALIZATION, 'deu'),
                    $visibleFrame,
                ],
                self::FOREIGN_COMMENT,
            ],
            'only described frames' => [
                [$itunesFrame, self::commentFrame('0', self::DESCRIBED_COMMENT)],
                null,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $frames
     */
    #[DataProvider('commentFramesProvider')]
    public function testVisibleCommentIsTheCommentFrameWithoutDescription(
        array $frames,
        ?string $expectedComment
    ): void {
        $media = $this->generateMedia();
        $this->seedId3v2Frames($media, ['COMM' => $frames]);

        self::assertCount(count($frames), Types::array($this->analyze($media)['id3v2']['COMM'] ?? []));

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame($expectedComment, $this->readFrameTexts($media, 'COMM')[''] ?? null);
    }

    public function testUserUrlWithoutDescriptionSurvivesSave(): void
    {
        $media = $this->generateMedia();
        $this->seedId3v2Frames($media, [
            'WXXX' => [
                self::userUrlFrame(self::DESCRIBED_URL_TAG, self::DESCRIBED_URL),
                self::userUrlFrame('', self::FOREIGN_URL),
            ],
        ]);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame(['' => self::FOREIGN_URL], $this->readFrameTexts($media, 'WXXX'));
    }

    public function testCommentAndUserUrlAreReadFromFramesWithoutDescription(): void
    {
        $media = $this->generateMedia();
        $this->seedId3v2Frames($media, [
            'COMM' => [
                self::commentFrame(self::ITUNES_NORMALIZATION_TAG, self::ITUNES_NORMALIZATION),
                self::commentFrame('', self::FOREIGN_COMMENT),
            ],
            'WXXX' => [
                self::userUrlFrame(self::DESCRIBED_URL_TAG, self::DESCRIBED_URL),
                self::userUrlFrame('', self::FOREIGN_URL),
            ],
        ]);

        $metadata = $this->readMetadata($media);

        self::assertSame(self::FOREIGN_COMMENT, $metadata->getKnownTags()['comment'] ?? null);
        self::assertSame(self::FOREIGN_URL, $metadata->getKnownTags()['url_user'] ?? null);

        // The numeric keys getID3 falls back to must not end up as tags of their own
        self::assertSame([], array_filter(array_keys($metadata->getExtraTags()), 'is_int'));
    }

    #[DataProvider('replaceablePictureFormatProvider')]
    public function testEmbeddedPictureIsKeptWhenCachedArtDiffers(string $extension): void
    {
        $media = $this->generateMedia($extension);

        self::assertTrue($this->mediaRepo->updateAlbumArt($media, $this->createCoverImage('red')));

        $pictureData = $this->readPictures($media)[0]['data'] ?? null;
        self::assertNotNull($pictureData);

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('blue'));

        $media->title = 'Changed Title';
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $pictures = $this->readPictures($media);
        self::assertCount(1, $pictures);
        self::assertSame($pictureData, $pictures[0]['data'] ?? null);

        $this->mediaRepo->removeAlbumArt($media);
        self::assertCount(0, $this->readPictures($media));
    }

    public function testForeignEmbeddedPicturesSurviveTagSave(): void
    {
        $media = $this->generateMedia();
        $this->embedCoversWithFfmpeg($media, [
            'Cover (front)' => $this->createCoverImageFile('red'),
            'Cover (back)' => $this->createCoverImageFile('blue'),
        ]);

        $pictures = array_column($this->readPictures($media), null, 'description');
        self::assertSame([3, 4], array_column($pictures, 'picturetypeid'));

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));

        $media->extra_metadata = ['cue_in' => 1.5];
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $picturesAfter = array_column($this->readPictures($media), null, 'description');
        self::assertSame(array_keys($pictures), array_keys($picturesAfter));

        foreach ($pictures as $description => $picture) {
            self::assertSame($picture['data'] ?? null, $picturesAfter[$description]['data'] ?? null);
            self::assertSame($picture['picturetypeid'] ?? null, $picturesAfter[$description]['picturetypeid'] ?? null);
        }
    }

    #[DataProvider('formatProvider')]
    public function testCachedArtIsEmbeddedWhenTheFileHasNoPicture(string $extension): void
    {
        $media = $this->generateMedia($extension);
        self::assertCount(0, $this->readPictures($media));

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));

        $media->title = 'Changed Title';
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $pictures = $this->readPictures($media);
        self::assertCount(1, $pictures);
        self::assertSame(
            file_get_contents($this->getLocalPath(StationMedia::getArtPath($media->unique_id))),
            $pictures[0]['data'] ?? null
        );
    }

    #[DataProvider('replaceablePictureFormatProvider')]
    public function testCachedArtReplacesLinkedPicture(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->seedLinkedPicture($media);

        $this->assertCachedArtIsEmbeddedOnSave($media);
    }

    public function testCachedArtIsEmbeddedNextToApeCover(): void
    {
        $media = $this->generateMedia();
        $this->seedApeCover($media, $this->createCoverImage('blue'));

        $this->assertCachedArtIsEmbeddedOnSave($media);
    }

    #[DataProvider('formatProvider')]
    public function testCustomFieldWithNativeTagIsWritten(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->addCustomField($media, 'composer', self::CUSTOM_COMPOSER);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame([self::CUSTOM_COMPOSER], $this->readFileTags($media)['composer'] ?? null);
    }

    public function testCustomFieldWithoutId3v2FrameIsWrittenAsTxxx(): void
    {
        $media = $this->generateMedia();
        $this->addCustomField($media, 'album_artist', self::CUSTOM_ALBUM_ARTIST);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame(
            self::CUSTOM_ALBUM_ARTIST,
            Types::array($this->readFileTags($media)['text'] ?? [])['album_artist'] ?? null
        );
        self::assertSame(
            self::CUSTOM_ALBUM_ARTIST,
            $this->readMetadata($media)->getKnownTags()['album_artist'] ?? null
        );
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function vorbisCommentNameProvider(): array
    {
        $tags = [
            'album artist' => ['album_artist', 'albumartist', self::CUSTOM_ALBUM_ARTIST],
            'track number' => ['track_number', 'tracknumber', self::FOREIGN_TRACK],
            'year' => ['year', 'date', self::CUSTOM_YEAR],
        ];

        $cases = [];
        foreach (self::vorbisFormatProvider() as $format => [$extension]) {
            foreach ($tags as $name => [$tag, $commentName, $value]) {
                $cases["{$format} {$name}"] = [$extension, $tag, $commentName, $value];
            }
        }

        return $cases;
    }

    #[DataProvider('vorbisCommentNameProvider')]
    public function testCustomFieldIsWrittenUnderStandardVorbisCommentName(
        string $extension,
        string $tag,
        string $commentName,
        string $value
    ): void {
        $media = $this->generateMedia($extension);
        $this->addCustomField($media, $tag, $value);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertSame([$value], $tags[$commentName] ?? null);
        self::assertArrayNotHasKey($tag, $tags);
        self::assertSame($value, $this->readMetadata($media)->getKnownTags()[$tag] ?? null);
    }

    public function testEmptyCustomFieldLeavesExistingTagUntouched(): void
    {
        $media = $this->generateMedia();
        $this->tagFile($media, ['composer' => self::FOREIGN_COMPOSER]);
        $this->addCustomField($media, 'composer', '');

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame([self::FOREIGN_COMPOSER], $this->readFileTags($media)['composer'] ?? null);
    }

    public function testEntityFieldWinsOverCustomFieldWithSameTag(): void
    {
        $media = $this->generateMedia();
        $this->addCustomField($media, 'title', 'Custom Title');

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame([self::TEST_TITLE], $this->readFileTags($media)['title'] ?? null);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function trackNumberProvider(): array
    {
        return [
            'imported track number' => [self::FOREIGN_TRACK_NUMBER, self::FOREIGN_TRACK],
            'track number with its own total' => ['8/10', '8/10'],
        ];
    }

    #[DataProvider('trackNumberProvider')]
    public function testCustomFieldWithTrackNumberWritesTrackTotal(string $trackNumber, string $expectedTrack): void
    {
        $media = $this->generateMedia();
        $this->tagFile($media, ['track' => self::FOREIGN_TRACK]);
        $this->addCustomField($media, 'track_number', $trackNumber);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame(['' => $expectedTrack], $this->readFrameTexts($media, 'TRCK'));
    }

    public function testCustomFieldTagIsReadBackByLoadFromFile(): void
    {
        $media = $this->generateMedia();
        $composerField = $this->addCustomField($media, 'composer', self::CUSTOM_COMPOSER);
        $albumArtistField = $this->addCustomField($media, 'album_artist', self::CUSTOM_ALBUM_ARTIST);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $this->mediaRepo->loadFromFile($media, $this->getLocalPath($media->path));
        $this->em->flush();

        $values = $this->readCustomFieldValues($media);

        self::assertSame(self::CUSTOM_COMPOSER, $values[$composerField->short_name] ?? null);
        self::assertSame(self::CUSTOM_ALBUM_ARTIST, $values[$albumArtistField->short_name] ?? null);
    }

    #[DataProvider('formatProvider')]
    public function testCustomFieldWithUnknownTagIsWritten(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->addCustomField($media, self::CUSTOM_TAG, self::CUSTOM_TAG_VALUE);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);

        if ($extension === 'mp3') {
            // The TXXX description keeps the exact spelling the admin entered
            self::assertSame(self::CUSTOM_TAG_VALUE, Types::array($tags['text'] ?? [])[self::CUSTOM_TAG] ?? null);
        } else {
            // Vorbis comment keys are case-insensitive and getID3 reports them lowercased
            self::assertSame([self::CUSTOM_TAG_VALUE], $tags[strtolower(self::CUSTOM_TAG)] ?? null);
        }
    }

    #[DataProvider('formatProvider')]
    public function testUnknownCustomFieldTagIsReadBackByLoadFromFile(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $customField = $this->addCustomField($media, self::CUSTOM_TAG, self::CUSTOM_TAG_VALUE);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $this->mediaRepo->loadFromFile($media, $this->getLocalPath($media->path));
        $this->em->flush();

        self::assertSame(
            self::CUSTOM_TAG_VALUE,
            $this->readCustomFieldValues($media)[$customField->short_name] ?? null
        );
    }

    #[DataProvider('formatProvider')]
    public function testZeroValueOfLinkedCustomFieldIsImported(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $customField = $this->addCustomField($media, self::CUSTOM_TAG, '0');

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $this->mediaRepo->loadFromFile($media, $this->getLocalPath($media->path));
        $this->em->flush();

        self::assertSame('0', $this->readCustomFieldValues($media)[$customField->short_name] ?? null);
    }

    public function testCustomFieldReplacesForeignTxxxWithDifferentCase(): void
    {
        $media = $this->generateMedia();
        $this->tagFile($media, [self::MUSICBRAINZ_ALBUM_ID_TAG => self::MUSICBRAINZ_ALBUM_ID]);
        $this->addCustomField(
            $media,
            strtoupper(self::MUSICBRAINZ_ALBUM_ID_TAG),
            self::CUSTOM_MUSICBRAINZ_ALBUM_ID
        );

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $expectedDescription = strtolower(self::MUSICBRAINZ_ALBUM_ID_TAG);
        $matchingFrames = array_filter(
            $this->readTxxxFrames($media),
            static fn(int|string $description): bool => strtolower((string) $description) === $expectedDescription,
            ARRAY_FILTER_USE_KEY
        );

        self::assertCount(1, $matchingFrames);
        self::assertSame(
            self::CUSTOM_MUSICBRAINZ_ALBUM_ID,
            $this->readMetadata($media)->getExtraTags()[$expectedDescription] ?? null
        );
    }

    public function testCustomFieldReplacesForeignTxxxWithAliasName(): void
    {
        $media = $this->generateMedia();
        $this->tagFile($media, ['website' => self::FOREIGN_URL]);
        $this->addCustomField($media, 'url_artist', self::CUSTOM_URL);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertArrayNotHasKey('website', Types::array($tags['text'] ?? []));
        self::assertSame([self::CUSTOM_URL], $tags['url_artist'] ?? null);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function urlTagProvider(): array
    {
        return [
            'WOAR' => ['url_artist'],
            'WCOP' => ['copyright'],
            'WCOM' => ['commercial_information'],
            'WXXX' => ['url_user'],
        ];
    }

    #[DataProvider('urlTagProvider')]
    public function testCustomFieldWithUrlTagReplacesUrlFrame(string $tag): void
    {
        $media = $this->generateMedia();
        $this->seedTagsWithGetId3($media, [$tag => [self::FOREIGN_URL]], ['id3v2.3']);
        $this->addCustomField($media, $tag, self::CUSTOM_URL);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame([self::CUSTOM_URL], $this->readFileTags($media)[$tag] ?? null);
        self::assertArrayNotHasKey($tag, $this->readTxxxFrames($media));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function urlTagValueWithoutUrlFrameProvider(): array
    {
        return [
            'e-mail address' => ['url_artist', self::EMAIL_URL],
            'user URL with e-mail address' => ['url_user', self::EMAIL_URL],
            'non-ASCII text' => ['copyright', self::NON_ASCII_COPYRIGHT],
        ];
    }

    #[DataProvider('urlTagValueWithoutUrlFrameProvider')]
    public function testCustomFieldUrlGetId3CannotWriteIsWrittenAsTxxx(string $tag, string $value): void
    {
        $media = $this->generateMedia();
        $this->addCustomField($media, $tag, $value);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertArrayNotHasKey($tag, $tags);
        self::assertSame($value, Types::array($tags['text'] ?? [])[$tag] ?? null);
    }

    #[DataProvider('urlTagValueWithoutUrlFrameProvider')]
    public function testUrlFrameGetId3CannotRewriteIsMovedToTxxx(string $tag, string $value): void
    {
        $media = $this->generateMedia();
        $this->seedUrlFrame($media, ID3v2::ID3v2ShortFrameNameLookup(3, $tag), $value);
        self::assertSame([$value], $this->readFileTags($media)[$tag] ?? null);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertArrayNotHasKey($tag, $tags);
        self::assertSame($value, Types::array($tags['text'] ?? [])[$tag] ?? null);
    }

    public function testId3v24OnlyTextFramesAreMovedToTxxx(): void
    {
        $media = $this->generateMedia();
        $this->seedTagsWithGetId3(
            $media,
            array_map(
                static fn(array $values): array => [implode(Id3v2Text::MULTI_VALUE_SEPARATOR, $values)],
                self::ID3V24_ONLY_TEXT_TAGS
            ),
            ['id3v2.4']
        );
        $knownTagsBefore = $this->readMetadata($media)->getKnownTags();

        self::assertTrue($this->mediaRepo->writeToFile($media));
        $hash = md5_file($this->getLocalPath($media->path));

        $text = Types::array($this->readFileTags($media)['text'] ?? []);
        $knownTags = $this->readMetadata($media)->getKnownTags();

        foreach (self::ID3V24_ONLY_TEXT_TAGS as $tag => $values) {
            $readValue = implode(Metadata::MULTI_VALUE_SEPARATOR . ' ', $values);

            self::assertSame($readValue, $knownTagsBefore[$tag] ?? null);
            self::assertSame(implode(Id3v2Text::MULTI_VALUE_SEPARATOR, $values), $text[$tag] ?? null);
            self::assertSame($readValue, $knownTags[$tag] ?? null);
        }

        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertSame($hash, md5_file($this->getLocalPath($media->path)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function vorbisFormatProvider(): array
    {
        return [
            'flac' => ['flac'],
            'ogg' => ['ogg'],
        ];
    }

    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function vorbisCommentAliasProvider(): array
    {
        $comments = [
            'date' => ['year', self::FOREIGN_DATE, self::CUSTOM_YEAR],
            'encodedby' => ['encoded_by', self::FOREIGN_ENCODED_BY, self::CUSTOM_ENCODED_BY],
            'albumartist' => ['album_artist', self::FOREIGN_ALBUM_ARTIST, self::CUSTOM_ALBUM_ARTIST],
            'album artist' => ['album_artist', self::FOREIGN_ALBUM_ARTIST, self::CUSTOM_ALBUM_ARTIST],
            'tracknumber' => ['track_number', self::FOREIGN_TRACK, '8/10'],
            'description' => ['comment', self::FOREIGN_COMMENT, self::CUSTOM_COMMENT],
        ];

        $cases = [];
        foreach (self::vorbisFormatProvider() as $format => [$extension]) {
            foreach ($comments as $commentName => [$tag, $foreignValue, $customValue]) {
                $cases["{$format} {$commentName}"] = [$extension, $commentName, $tag, $foreignValue, $customValue];
            }
        }

        return $cases;
    }

    #[DataProvider('vorbisCommentAliasProvider')]
    public function testCustomFieldReplacesVorbisCommentWithAliasName(
        string $extension,
        string $commentName,
        string $tag,
        string $foreignValue,
        string $customValue
    ): void {
        $media = $this->generateMedia($extension);
        $this->tagFile($media, [$commentName => $foreignValue]);
        $this->addCustomField($media, $tag, $customValue);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertSame([$customValue], $tags[$commentName] ?? null);
        self::assertArrayNotHasKey($tag, $tags);
        self::assertSame($customValue, $this->readMetadata($media)->getKnownTags()[$tag] ?? null);
    }

    #[DataProvider('vorbisFormatProvider')]
    public function testEncodedByLeavesEncoderCommentUntouched(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $ffmpegEncoder = $this->readFileTags($media)['encoder'] ?? null;
        self::assertIsArray($ffmpegEncoder);

        $this->addCustomField($media, 'encoded_by', self::CUSTOM_ENCODED_BY);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertSame($ffmpegEncoder, $tags['encoder'] ?? null);
        self::assertSame([self::CUSTOM_ENCODED_BY], $tags['encodedby'] ?? null);

        $knownTags = $this->readMetadata($media)->getKnownTags();
        self::assertSame(self::CUSTOM_ENCODED_BY, $knownTags['encoded_by'] ?? null);
        self::assertSame($ffmpegEncoder[0] ?? null, $knownTags['encoder_settings'] ?? null);
    }

    #[DataProvider('vorbisFormatProvider')]
    public function testEncoderSettingsReplaceEncoderComment(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->addCustomField($media, 'encoder_settings', self::CUSTOM_ENCODER_SETTINGS);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertSame([self::CUSTOM_ENCODER_SETTINGS], $tags['encoder'] ?? null);
        self::assertArrayNotHasKey('encoder_settings', $tags);
    }

    #[DataProvider('vorbisFormatProvider')]
    public function testLyricsReplaceUnsynchronisedLyricComment(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->tagFile($media, ['unsynchronised_lyric' => self::FOREIGN_LYRICS]);
        $media->lyrics = self::CUSTOM_LYRICS;

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        self::assertSame([self::CUSTOM_LYRICS], $tags['lyrics'] ?? null);
        self::assertArrayNotHasKey('unsynchronised_lyric', $tags);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function lyricsCommentProvider(): array
    {
        return [
            'flac lyrics' => ['flac', 'lyrics'],
            'ogg lyrics' => ['ogg', 'lyrics'],
            'flac unsyncedlyrics' => ['flac', 'unsyncedlyrics'],
            'ogg unsyncedlyrics' => ['ogg', 'unsyncedlyrics'],
        ];
    }

    #[DataProvider('lyricsCommentProvider')]
    public function testClearedLyricsRemoveLyricsComment(string $extension, string $commentName): void
    {
        $media = $this->generateMedia($extension);
        $this->tagFile($media, [$commentName => self::FOREIGN_LYRICS]);

        self::assertSame(
            self::FOREIGN_LYRICS,
            $this->readMetadata($media)->getKnownTags()['unsynchronised_lyric'] ?? null
        );

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertArrayNotHasKey('unsynchronised_lyric', $this->readMetadata($media)->getKnownTags());
    }

    public function testVorbisCommentNamesInTxxxFramesSurviveSave(): void
    {
        $media = $this->generateMedia();
        $this->seedTagsWithGetId3(
            $media,
            [
                'text' => [
                    [
                        'encodingid' => 0,
                        'description' => 'LYRICS',
                        'data' => self::FOREIGN_LYRICS,
                    ],
                    [
                        'encodingid' => 0,
                        'description' => 'DESCRIPTION',
                        'data' => self::FOREIGN_DESCRIPTION,
                    ],
                ],
            ],
            ['id3v2.3']
        );
        $media->lyrics = self::CUSTOM_LYRICS;
        $this->addCustomField($media, 'comment', self::CUSTOM_COMMENT);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $txxxFrames = $this->readTxxxFrames($media);
        self::assertSame(self::FOREIGN_LYRICS, $txxxFrames['LYRICS']['data'] ?? null);
        self::assertSame(self::FOREIGN_DESCRIPTION, $txxxFrames['DESCRIPTION']['data'] ?? null);
    }

    public function testEmptyUnknownCustomFieldLeavesExistingTagUntouched(): void
    {
        $media = $this->generateMedia();
        $this->tagFile($media, [self::MUSICBRAINZ_ALBUM_ID_TAG => self::MUSICBRAINZ_ALBUM_ID]);
        $this->addCustomField($media, self::MUSICBRAINZ_ALBUM_ID_TAG, '');

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame(
            self::MUSICBRAINZ_ALBUM_ID,
            Types::array($this->readFileTags($media)['text'] ?? [])[self::MUSICBRAINZ_ALBUM_ID_TAG] ?? null
        );
    }

    private function generateMedia(string $extension = 'mp3'): StationMedia
    {
        $media = $this->dummyMediaGenerator->generate(
            $this->storageLocation,
            new MediaEntry(
                ref: 'media',
                path: 'metadata-writer-test.' . $extension,
                uniqueId: '',
                length: 3.0,
                artist: self::TEST_ARTIST,
                title: self::TEST_TITLE,
                album: self::TEST_ALBUM,
                genre: null
            )
        );

        self::assertInstanceOf(StationMedia::class, $media);
        $this->generatedMedia[] = $media;

        return $media;
    }

    /**
     * @return array<string, string>
     */
    private function getForeignTags(): array
    {
        return [
            'title' => self::FOREIGN_TITLE,
            'date' => self::FOREIGN_DATE,
            'track' => self::FOREIGN_TRACK,
            'comment' => self::FOREIGN_COMMENT,
            'publisher' => self::FOREIGN_PUBLISHER,
            'replaygain_track_gain' => self::REPLAYGAIN_TRACK_GAIN,
            'replaygain_track_peak' => self::REPLAYGAIN_TRACK_PEAK,
            self::MUSICBRAINZ_ALBUM_ID_TAG => self::MUSICBRAINZ_ALBUM_ID,
        ];
    }

    /**
     * Remuxes the media file with ffmpeg to add tags the way an external tagger would.
     *
     * @param array<string, string> $tags
     */
    private function tagFile(StationMedia $media, array $tags, bool $id3v1Only = false): void
    {
        $path = $this->getLocalPath($media->path);
        $taggedPath = $this->storagePath . '/tagged.' . pathinfo($path, PATHINFO_EXTENSION);

        $arguments = [
            '-y',
            '-i',
            $path,
            '-map',
            '0:a',
            '-c:a',
            'copy',
            ...($id3v1Only ? ['-id3v2_version', '0', '-write_id3v1', '1'] : ['-id3v2_version', '3']),
        ];
        foreach ($tags as $key => $value) {
            $arguments[] = '-metadata';
            $arguments[] = $key . '=' . $value;
        }
        $arguments[] = $taggedPath;

        $this->ffmpeg->getFFMpegDriver()->command($arguments);

        rename($taggedPath, $path);
    }

    /**
     * Seeds tags straight through getID3's writer, for fixtures ffmpeg cannot produce.
     *
     * @param array<string, mixed> $tagData
     * @param list<string> $tagFormats
     */
    private function seedTagsWithGetId3(StationMedia $media, array $tagData, array $tagFormats): void
    {
        $tagWriter = new WriteTags();
        $tagWriter->filename = $this->getLocalPath($media->path);
        $tagWriter->tagformats = $tagFormats;
        $tagWriter->tag_data = $tagData;
        $tagWriter->overwrite_tags = true;
        $tagWriter->remove_other_tags = false;
        $tagWriter->tag_encoding = 'UTF-8';
        $tagWriter->WriteTags();

        if (!empty($tagWriter->errors)) {
            throw new RuntimeException(implode(', ', $tagWriter->errors));
        }
    }

    /**
     * Writes raw ID3v2 frames, bypassing WriteTags which drops descriptions of COMM frames.
     *
     * @param array<string, list<array<string, mixed>>> $frames
     */
    private function seedId3v2Frames(StationMedia $media, array $frames): void
    {
        $id3v2Writer = new ID3v2();
        $id3v2Writer->filename = $this->getLocalPath($media->path);
        $id3v2Writer->majorversion = 3;
        $id3v2Writer->tag_data = $frames;

        if (!$id3v2Writer->WriteID3v2()) {
            throw new RuntimeException(implode(', ', $id3v2Writer->errors));
        }
    }

    /**
     * Writes a URL frame as ISO-8859-1, including an @ which getID3 refuses to write.
     */
    private function seedUrlFrame(StationMedia $media, string $frameName, string $url): void
    {
        $latin1Url = mb_convert_encoding($url, 'ISO-8859-1', 'UTF-8');
        $placeholder = str_repeat('x', strlen($latin1Url));

        $this->seedId3v2Frames($media, [$frameName => [self::userUrlFrame('', $placeholder)]]);

        $path = $this->getLocalPath($media->path);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, str_replace($placeholder, $latin1Url, $filesystem->readFile($path)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function commentFrame(string $description, string $text, string $language = 'eng'): array
    {
        return [
            'encodingid' => 0,
            'language' => $language,
            'description' => $description,
            'data' => $text,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function userUrlFrame(string $description, string $url): array
    {
        return [
            'encodingid' => 0,
            'description' => $description,
            'data' => $url,
        ];
    }

    /**
     * @return array<string, ?string>
     */
    private function readCustomFieldValues(StationMedia $media): array
    {
        $values = [];
        foreach ($media->custom_fields as $customFieldRow) {
            $values[$customFieldRow->field->short_name] = $customFieldRow->value;
        }

        return $values;
    }

    private function addCustomField(StationMedia $media, string $autoAssign, ?string $value): CustomField
    {
        $customField = new CustomField();
        $customField->name = 'Test ' . $autoAssign;
        $customField->auto_assign = $autoAssign;
        $this->em->persist($customField);
        $this->customFields[] = $customField;

        $customFieldRow = new StationMediaCustomField($media, $customField);
        $customFieldRow->value = $value;
        $this->em->persist($customFieldRow);
        $media->custom_fields->add($customFieldRow);

        $this->em->flush();

        return $customField;
    }

    private function createCoverImage(string $color): string
    {
        return (string) file_get_contents($this->createCoverImageFile($color));
    }

    private function createCoverImageFile(string $color): string
    {
        $coverPath = $this->storagePath . '/cover-' . $color . '.jpg';

        $this->ffmpeg->getFFMpegDriver()->command([
            '-y',
            '-f',
            'lavfi',
            '-i',
            'color=c=' . $color . ':s=64x64:d=1',
            '-frames:v',
            '1',
            $coverPath,
        ]);

        return $coverPath;
    }

    /**
     * Embeds covers the way an external tagger would, without touching AzuraCast's album art cache.
     *
     * @param array<string, string> $coverPathsByType Image paths keyed by ffmpeg picture type, e.g. "Cover (back)"
     */
    private function embedCoversWithFfmpeg(StationMedia $media, array $coverPathsByType): void
    {
        $path = $this->getLocalPath($media->path);
        $taggedPath = $this->storagePath . '/with-cover.' . pathinfo($path, PATHINFO_EXTENSION);

        $arguments = ['-y', '-i', $path];
        foreach ($coverPathsByType as $coverPath) {
            array_push($arguments, '-i', $coverPath);
        }
        array_push($arguments, '-map', '0:a', '-c', 'copy', '-id3v2_version', '3');

        foreach (array_keys($coverPathsByType) as $streamIndex => $pictureType) {
            $inputIndex = $streamIndex + 1;

            array_push(
                $arguments,
                '-map',
                "{$inputIndex}:v",
                "-disposition:v:{$streamIndex}",
                'attached_pic',
                "-metadata:s:v:{$streamIndex}",
                "title={$pictureType}",
                "-metadata:s:v:{$streamIndex}",
                "comment={$pictureType}"
            );
        }
        $arguments[] = $taggedPath;

        $this->ffmpeg->getFFMpegDriver()->command($arguments);

        rename($taggedPath, $path);
    }

    /**
     * Embeds a picture that only links to an image, which the writer cannot keep.
     */
    private function seedLinkedPicture(StationMedia $media): void
    {
        if (pathinfo($media->path, PATHINFO_EXTENSION) === 'mp3') {
            $this->seedId3v2Frames($media, [
                'APIC' => [
                    [
                        'encodingid' => 0,
                        'mime' => self::LINKED_PICTURE_MIME,
                        'picturetypeid' => 3,
                        'description' => '',
                        'data' => self::LINKED_PICTURE_URL,
                    ],
                ],
            ]);

            return;
        }

        $pictureBlock = self::encodePictureBlock(self::LINKED_PICTURE_MIME, self::LINKED_PICTURE_URL);

        $this->seedTagsWithGetId3($media, ['METADATA_BLOCK_PICTURE' => [$pictureBlock]], ['vorbiscomment']);
    }

    /**
     * Writes an APE cover item, which getID3 can only write as text.
     */
    private function seedApeCover(StationMedia $media, string $cover): void
    {
        $placeholder = str_repeat('x', strlen($cover));

        $this->seedTagsWithGetId3($media, ['COVER ART (FRONT)' => ['cover.jpg', $placeholder]], ['ape']);

        $path = $this->getLocalPath($media->path);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, str_replace($placeholder, $cover, $filesystem->readFile($path)));
    }

    private static function encodePictureBlock(string $mime, string $data): string
    {
        return base64_encode(
            pack('N', 3)
            . pack('N', strlen($mime)) . $mime
            . pack('N', 0)
            . pack('N4', 0, 0, 0, 0)
            . pack('N', strlen($data)) . $data
        );
    }

    private function assertCachedArtIsEmbeddedOnSave(StationMedia $media): void
    {
        self::assertCount(1, Types::array($this->analyze($media)['comments']['picture'] ?? []));

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));

        $media->title = 'Changed Title';
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $pictures = $this->readPictures($media);
        self::assertCount(1, $pictures);
        self::assertSame(
            file_get_contents($this->getLocalPath(StationMedia::getArtPath($media->unique_id))),
            $pictures[0]['data'] ?? null
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readTxxxFrames(StationMedia $media): array
    {
        $frames = [];
        foreach (Types::array($this->analyze($media)['id3v2']['TXXX'] ?? []) as $frame) {
            $frame = Types::array($frame);
            $frames[Types::string($frame['description'] ?? null)] = $frame;
        }

        return $frames;
    }

    /**
     * @return array<string, string> Text of each frame with the given name, keyed by its description
     */
    private function readFrameTexts(StationMedia $media, string $frameName): array
    {
        $texts = [];
        foreach (Types::array($this->analyze($media)['id3v2'][$frameName] ?? []) as $frame) {
            $frame = Types::array($frame);

            // getID3 keeps the link of URL frames apart from their data
            $texts[Types::string($frame['description'] ?? null)] = isset($frame['url'])
                ? Types::string($frame['url'])
                : Id3v2Text::decode(
                    Types::string($frame['encoding'] ?? null, 'ISO-8859-1'),
                    Types::string($frame['data'] ?? null)
                );
        }

        return $texts;
    }

    private function getLocalPath(string $path): string
    {
        return $this->storagePath . '/' . $path;
    }

    private function readMetadata(StationMedia $media): MetadataInterface
    {
        return $this->metadataManager->read($this->getLocalPath($media->path));
    }

    /**
     * @return array<string, mixed>
     */
    private function analyze(StationMedia $media): array
    {
        return (new GetID3())->analyze($this->getLocalPath($media->path));
    }

    /**
     * @return array<string, mixed>
     */
    private function readFileTags(StationMedia $media): array
    {
        $container = pathinfo($media->path, PATHINFO_EXTENSION) === 'mp3' ? 'id3v2' : 'vorbiscomment';

        return Types::array($this->analyze($media)['tags'][$container] ?? []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readPictures(StationMedia $media): array
    {
        $info = $this->analyze($media);

        $pictures = match (pathinfo($media->path, PATHINFO_EXTENSION)) {
            'mp3' => $info['id3v2']['APIC'] ?? [],
            'flac' => $info['flac']['PICTURE'] ?? [],
            default => $info['comments']['picture'] ?? [],
        };

        return array_values(array_map(
            static fn(mixed $picture): array => Types::array($picture),
            Types::array($pictures)
        ));
    }
}
