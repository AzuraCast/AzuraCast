<?php

declare(strict_types=1);

namespace Unit;

use App\Nginx\CustomUrls;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

final class NginxCustomUrlsTest extends Unit
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function xAccelPathProvider(): array
    {
        return [
            'plain path' => [
                '/var/azuracast/stations/test_station/media/Artist/song.mp3',
                '/internal/stations/test_station/media/Artist/song.mp3',
            ],
            'percent sign and spaces' => [
                '/var/azuracast/stations/test_station/media/100 % Rock/01 Easy Livin\'.mp3',
                '/internal/stations/test_station/media/100%20%25%20Rock/01%20Easy%20Livin%27.mp3',
            ],
            'query and fragment characters' => [
                '/var/azuracast/stations/test_station/media/What?/#1 Hit.mp3',
                '/internal/stations/test_station/media/What%3F/%231%20Hit.mp3',
            ],
            'non-ascii characters' => [
                '/var/azuracast/stations/test_station/media/Björk/Jóga.mp3',
                '/internal/stations/test_station/media/Bj%C3%B6rk/J%C3%B3ga.mp3',
            ],
            'storage directory' => [
                '/var/azuracast/storage/uploads/cover 50%.jpg',
                '/internal/storage/uploads/cover%2050%25.jpg',
            ],
            'backups directory' => [
                '/var/azuracast/backups/backup 2026-11-28.zip',
                '/internal/backups/backup%202026-11-28.zip',
            ],
        ];
    }

    #[DataProvider('xAccelPathProvider')]
    public function testXAccelPathEncodesEachPathSegment(string $path, string $expected): void
    {
        self::assertSame($expected, CustomUrls::getXAccelPath($path));
    }

    public function testXAccelPathIsNullForPathsNginxDoesNotServe(): void
    {
        self::assertNull(CustomUrls::getXAccelPath('/tmp/100 % Rock.mp3'));
    }
}
