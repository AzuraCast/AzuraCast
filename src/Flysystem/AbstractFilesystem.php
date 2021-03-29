<?php

namespace App\Flysystem;

use App\Flysystem\Adapter\AdapterInterface;
use App\Http\Response;
use League\Flysystem\PathNormalizer;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractFilesystem extends \League\Flysystem\Filesystem implements FilesystemInterface
{
    protected AdapterInterface $adapter;

    public function __construct(AdapterInterface $adapter, array $config = [], PathNormalizer $pathNormalizer = null)
    {
        $this->adapter = $adapter;

        parent::__construct($adapter, $config, $pathNormalizer);
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getMetadata(string $path): StorageAttributes
    {
        return $this->adapter->getMetadata($path);
    }

    public function uploadAndDeleteOriginal(string $localPath, string $to): void
    {
        $this->upload($localPath, $to);
        @unlink($localPath);
    }

    protected function doStreamToResponse(
        Response $response,
        string $localPath,
        int $fileSize,
        string $mimeType = 'application/octet-stream',
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface {
        $fileName ??= basename($localPath);

        if ('attachment' === $disposition) {
            /*
             * The regex used below is to ensure that the $fileName contains only
             * characters ranging from ASCII 128-255 and ASCII 0-31 and 127 are replaced with an empty string
             */
            $disposition .= '; filename="' . preg_replace('/[\x00-\x1F\x7F\"]/', ' ', $fileName) . '"';
            $disposition .= "; filename*=UTF-8''" . rawurlencode($fileName);
        }

        $response = $response->withHeader('Content-Disposition', $disposition)
            ->withHeader('Content-Length', $fileSize)
            ->withHeader('X-Accel-Buffering', 'no');

        // Special internal nginx routes to use X-Accel-Redirect for far more performant file serving.
        $specialPaths = [
            '/var/azuracast/backups' => '/internal/backups',
            '/var/azuracast/stations' => '/internal/stations',
        ];

        foreach ($specialPaths as $diskPath => $nginxPath) {
            if (0 === strpos($localPath, $diskPath)) {
                $accelPath = str_replace($diskPath, $nginxPath, $localPath);

                // Temporary work around, see SlimPHP/Slim#2924
                $response->getBody()->write(' ');

                return $response->withHeader('Content-Type', $mimeType)
                    ->withHeader('X-Accel-Redirect', $accelPath);
            }
        }

        return $response->withFile($localPath, $mimeType);
    }
}
