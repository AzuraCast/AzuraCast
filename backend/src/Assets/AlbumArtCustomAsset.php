<?php

declare(strict_types=1);

namespace App\Assets;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Station;
use App\Service\Vite;
use DI\Attribute\Injectable;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Psr\Http\Message\UriInterface;

#[Injectable(lazy: true)]
final class AlbumArtCustomAsset extends AbstractMultiPatternCustomAsset
{
    public function __construct(
        Vite $vite,
        private readonly SettingsRepository $settingsRepo
    ) {
        parent::__construct($vite);
    }

    protected function getPatterns(): array
    {
        return [
            'default' => [
                'album_art%s.jpg',
                new JpegEncoder(90),
            ],
            'image/png' => [
                'album_art%s.png',
                new PngEncoder(),
            ],
            'image/webp' => [
                'album_art%s.webp',
                new WebpEncoder(90),
            ],
        ];
    }

    protected function getDefaultUrl(): string
    {
        return $this->vite->getImagePath('img/generic_song.jpg');
    }

    public function upload(
        ImageInterface $image,
        string $mimeType,
        ?Station $station = null
    ): void {
        $newImage = clone $image;
        $newImage->resizeDown(1500, 1500);

        $this->delete($station);

        $patterns = $this->getPatterns();
        [$pattern, $encoder] = $patterns[$mimeType] ?? $patterns['default'];

        $destPath = $this->getPathForPattern($pattern, $station);

        $this->ensureDirectoryExists(dirname($destPath));

        $newImage->encode($encoder)->save($destPath);
    }

    /**
     * Return the URL to use for songs with no specified album artwork, when artwork is displayed.
     */
    public function getDefaultAlbumArtUrl(?Station $station = null): UriInterface
    {
        if (null !== $station) {
            if ($this->isUploaded($station)) {
                return $this->getUri($station);
            }

            $stationCustomUri = $station->branding_config->getDefaultAlbumArtUrlAsUri();
            if (null !== $stationCustomUri) {
                return $stationCustomUri;
            }
        }

        $settings = $this->settingsRepo->readSettings();

        $customUrl = $settings->getDefaultAlbumArtUrlAsUri();
        return $customUrl ?? $this->getUri();
    }
}
