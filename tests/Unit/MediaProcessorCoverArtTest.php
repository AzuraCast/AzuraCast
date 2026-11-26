<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\StorageLocation;
use App\Exception\CannotProcessMediaException;
use App\Media\MediaProcessor;
use App\Tests\Module;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Symfony\Component\Filesystem\Filesystem;

final class MediaProcessorCoverArtTest extends Unit
{
    private MediaProcessor $mediaProcessor;

    private string $storageDir;

    protected function _inject(Module $testsModule): void
    {
        $this->mediaProcessor = $testsModule->container->get(MediaProcessor::class);
    }

    protected function _before(): void
    {
        $this->storageDir = sys_get_temp_dir() . '/' . uniqid('cover_art_test_', true);
        new Filesystem()->mkdir($this->storageDir);
    }

    protected function _after(): void
    {
        new Filesystem()->remove($this->storageDir);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function undecodableContentsProvider(): array
    {
        return [
            'null bytes' => [str_repeat("\0", 4096)],
            'html page' => ["<html>\n<head><title>Not Found</title></head>\n</html>\n"],
            'printable text' => ['not an image'],
        ];
    }

    #[DataProvider('undecodableContentsProvider')]
    public function testUndecodableCoverArtThrowsCannotProcessMediaException(string $contents): void
    {
        $storageLocation = new StorageLocation(
            StorageLocationTypes::StationMedia,
            StorageLocationAdapters::Local
        );
        $storageLocation->path = $this->storageDir;

        try {
            $this->mediaProcessor->processCoverArt($storageLocation, 'Artist/Album/folder.jpg', $contents);
            self::fail('Expected a CannotProcessMediaException for undecodable cover art.');
        } catch (CannotProcessMediaException $e) {
            self::assertSame('folder.jpg', $e->getPath());
            self::assertStringStartsWith('Cover art could not be decoded:', $e->getMessage());
        }

        self::assertDirectoryDoesNotExist($this->storageDir . '/.covers');
    }
}
