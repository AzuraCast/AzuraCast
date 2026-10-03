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
    private const string FOREIGN_URL = 'https://example.com/artist';
    private const string ASCII_PUBLISHER = 'ASCII Records';
    private const string FOREIGN_COMPOSER = 'Seeded Composer';
    private const string CUSTOM_COMPOSER = 'Custom Composer';
    private const string CUSTOM_ALBUM_ARTIST = 'Custom Album Artist';
    private const string CUSTOM_TAG = 'Likes';
    private const string CUSTOM_TAG_VALUE = '42';
    private const string CUSTOM_MUSICBRAINZ_ALBUM_ID = '9a8b7c6d-1111-2222-3333-444455556666';

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

        if ($extension === 'mp3') {
            self::assertSame(self::FOREIGN_COMMENT, $knownTags['comment'] ?? null);
            self::assertSame(self::FOREIGN_TRACK_NUMBER, $knownTags['track_number'] ?? null);
            self::assertSame(self::FOREIGN_TRACK_TOTAL, $extraTags['totaltracks'] ?? null);
        } else {
            // ffmpeg stores a Vorbis comment as DESCRIPTION, which getID3 reports under that name.
            self::assertSame(self::FOREIGN_COMMENT, $extraTags['description'] ?? null);
            self::assertSame(self::FOREIGN_TRACK, $extraTags['tracknumber'] ?? null);
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

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));
        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertCount(1, $this->readPictures($media));

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('blue'));
        self::assertTrue($this->mediaRepo->writeToFile($media));

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

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));
        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertCount(1, $this->readPictures($media));

        self::assertTrue($this->mediaRepo->writeToFile($media));
        self::assertCount(1, $this->readPictures($media));

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('blue'));
        self::assertTrue($this->mediaRepo->writeToFile($media));
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

    #[DataProvider('replaceablePictureFormatProvider')]
    public function testEmbeddedPictureIsKeptWhenCachedArtIsMissing(string $extension): void
    {
        $media = $this->generateMedia($extension);

        $this->mediaRepo->writeAlbumArt($media, $this->createCoverImage('red'));
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $pictureData = $this->readPictures($media)[0]['data'] ?? null;
        self::assertNotNull($pictureData);

        unlink($this->getLocalPath(StationMedia::getArtPath($media->unique_id)));

        $media->title = 'Changed Title';
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $pictures = $this->readPictures($media);
        self::assertCount(1, $pictures);
        self::assertSame($pictureData, $pictures[0]['data'] ?? null);

        $this->mediaRepo->removeAlbumArt($media);
        self::assertCount(0, $this->readPictures($media));
    }

    public function testForeignEmbeddedPictureSurvivesSaveWithoutCachedArt(): void
    {
        $media = $this->generateMedia();
        $this->embedCoverWithFfmpeg($media, $this->createCoverImageFile('red'));

        $pictures = $this->readPictures($media);
        self::assertCount(1, $pictures);

        $media->extra_metadata = ['cue_in' => 1.5];
        self::assertTrue($this->mediaRepo->writeToFile($media));

        $picturesAfter = $this->readPictures($media);
        self::assertCount(1, $picturesAfter);
        self::assertSame($pictures[0]['data'] ?? null, $picturesAfter[0]['data'] ?? null);
        self::assertSame($pictures[0]['picturetypeid'] ?? null, $picturesAfter[0]['picturetypeid'] ?? null);
    }

    #[DataProvider('formatProvider')]
    public function testCustomFieldWithNativeTagIsWritten(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->addCustomField($media, 'composer', self::CUSTOM_COMPOSER);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame([self::CUSTOM_COMPOSER], $this->readFileTags($media)['composer'] ?? null);
    }

    #[DataProvider('formatProvider')]
    public function testCustomFieldWithoutId3v2FrameIsWrittenAsTxxx(string $extension): void
    {
        $media = $this->generateMedia($extension);
        $this->addCustomField($media, 'album_artist', self::CUSTOM_ALBUM_ARTIST);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        $tags = $this->readFileTags($media);
        $fileValue = $extension === 'mp3'
            ? Types::array($tags['text'] ?? [])['album_artist'] ?? null
            : Types::array($tags['album_artist'] ?? [])[0] ?? null;

        self::assertSame(self::CUSTOM_ALBUM_ARTIST, $fileValue);
        self::assertSame(
            self::CUSTOM_ALBUM_ARTIST,
            $this->readMetadata($media)->getKnownTags()['album_artist'] ?? null
        );
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

    public function testCustomFieldWithKnownTagInNonCanonicalCaseIsImported(): void
    {
        $media = $this->generateMedia();
        $customField = $this->addCustomField($media, 'Composer', self::CUSTOM_COMPOSER);

        self::assertTrue($this->mediaRepo->writeToFile($media));

        self::assertSame([self::CUSTOM_COMPOSER], $this->readFileTags($media)['composer'] ?? null);

        $this->mediaRepo->loadFromFile($media, $this->getLocalPath($media->path));
        $this->em->flush();

        self::assertSame(
            self::CUSTOM_COMPOSER,
            $this->readCustomFieldValues($media)[$customField->short_name] ?? null
        );
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
     * Embeds a cover the way an external tagger would, without touching AzuraCast's album art cache.
     */
    private function embedCoverWithFfmpeg(StationMedia $media, string $coverPath): void
    {
        $path = $this->getLocalPath($media->path);
        $taggedPath = $this->storagePath . '/with-cover.' . pathinfo($path, PATHINFO_EXTENSION);

        $this->ffmpeg->getFFMpegDriver()->command([
            '-y',
            '-i',
            $path,
            '-i',
            $coverPath,
            '-map',
            '0:a',
            '-map',
            '1:v',
            '-c',
            'copy',
            '-id3v2_version',
            '3',
            '-disposition:v',
            'attached_pic',
            '-metadata:s:v',
            'title=Album cover',
            '-metadata:s:v',
            'comment=Cover (front)',
            $taggedPath,
        ]);

        rename($taggedPath, $path);
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
