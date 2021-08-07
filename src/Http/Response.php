<?php

declare(strict_types=1);

namespace App\Http;

use Azura\Files\Adapter\LocalAdapterInterface;
use Azura\Files\ExtendedFilesystemInterface;
use InvalidArgumentException;
use League\Flysystem\FileAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response extends \Slim\Http\Response
{
    public const CACHE_ONE_MINUTE = 60;
    public const CACHE_ONE_HOUR = 3600;
    public const CACHE_ONE_DAY = 86400;
    public const CACHE_ONE_MONTH = 2592000;
    public const CACHE_ONE_YEAR = 31536000;

    /**
     * Send headers that expire the content immediately and prevent caching.
     * @return static
     */
    public function withNoCache(): static
    {
        $response = $this->response
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', 0))
            ->withHeader('Cache-Control', 'private, no-cache, no-store')
            ->withHeader('X-Accel-Expires', '0'); // CloudFlare

        return new static($response, $this->streamFactory);
    }

    /**
     * Send headers that expire the content in the specified number of seconds.
     *
     * @param int $seconds
     *
     * @return static
     */
    public function withCacheLifetime(int $seconds = self::CACHE_ONE_MONTH): static
    {
        $response = $this->response
            ->withHeader('Pragma', '')
            ->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $seconds))
            ->withHeader('Cache-Control', 'public, must-revalidate, max-age=' . $seconds)
            ->withHeader('X-Accel-Expires', (string)$seconds); // CloudFlare

        return new static($response, $this->streamFactory);
    }

    /**
     * Returns whether the request has a "cache lifetime" assigned to it.
     */
    public function hasCacheLifetime(): bool
    {
        if ($this->response->hasHeader('Pragma')) {
            return (!str_contains($this->response->getHeaderLine('Pragma'), 'no-cache'));
        }

        return (!str_contains($this->response->getHeaderLine('Cache-Control'), 'no-cache'));
    }

    /**
     * Don't escape forward slashes by default on JSON responses.
     *
     * @param mixed $data
     * @param int|null $status
     * @param int $options
     * @param int $depth
     */
    public function withJson($data, ?int $status = null, int $options = 0, int $depth = 512): ResponseInterface
    {
        $options |= JSON_UNESCAPED_SLASHES;
        $options |= JSON_UNESCAPED_UNICODE;

        return parent::withJson($data, $status, $options, $depth);
    }

    /**
     * Stream the contents of a file directly through to the response.
     *
     * @param string $file_path
     * @param null $file_name
     *
     * @return static
     */
    public function renderFile(string $file_path, $file_name = null): static
    {
        set_time_limit(600);

        if (null === $file_name) {
            $file_name = basename($file_path);
        }

        $stream = $this->streamFactory->createStreamFromFile($file_path);

        $response = $this->response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', mime_content_type($file_path) ?: '')
            ->withHeader('Content-Length', (string)filesize($file_path))
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name)
            ->withBody($stream);

        return new static($response, $this->streamFactory);
    }

    /**
     * Write a string of file data to the response as if it is a file for download.
     *
     * @param string $file_data
     * @param string $content_type
     * @param string|null $file_name
     *
     * @return static
     */
    public function renderStringAsFile(string $file_data, string $content_type, ?string $file_name = null): static
    {
        $response = $this->response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', $content_type);

        if ($file_name !== null) {
            $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $file_name);
        }

        $response->getBody()->write($file_data);

        return new static($response, $this->streamFactory);
    }

    /**
     * Write a stream to the response as if it is a file for download.
     *
     * @param StreamInterface $fileStream
     * @param string $contentType
     * @param string|null $fileName
     *
     * @return static
     */
    public function renderStreamAsFile(
        StreamInterface $fileStream,
        string $contentType,
        ?string $fileName = null
    ): static {
        set_time_limit(600);

        $response = $this->response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', $contentType);

        if ($fileName !== null) {
            $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        }

        $response = $response->withBody($fileStream);

        return new static($response, $this->streamFactory);
    }

    public function streamFilesystemFile(
        ExtendedFilesystemInterface $filesystem,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface {
        /** @var FileAttributes $fileMeta */
        $fileMeta = $filesystem->getMetadata($path);
        if (!$fileMeta->isFile()) {
            throw new InvalidArgumentException('Specified file is not a file!');
        }

        $fileName ??= basename($path);

        if ('attachment' === $disposition) {
            /*
             * The regex used below is to ensure that the $fileName contains only
             * characters ranging from ASCII 128-255 and ASCII 0-31 and 127 are replaced with an empty string
             */
            $disposition .= '; filename="' . preg_replace('/[\x00-\x1F\x7F\"]/', ' ', $fileName) . '"';
            $disposition .= "; filename*=UTF-8''" . rawurlencode($fileName);
        }

        $response = $this->withHeader('Content-Disposition', $disposition)
            ->withHeader('Content-Length', (string)$fileMeta->fileSize())
            ->withHeader('X-Accel-Buffering', 'no');

        $adapter = $filesystem->getAdapter();
        if ($adapter instanceof LocalAdapterInterface) {
            $localPath = $filesystem->getLocalPath($path);

            // Special internal nginx routes to use X-Accel-Redirect for far more performant file serving.
            $specialPaths = [
                '/var/azuracast/backups' => '/internal/backups',
                '/var/azuracast/stations' => '/internal/stations',
            ];

            foreach ($specialPaths as $diskPath => $nginxPath) {
                if (str_starts_with($localPath, $diskPath)) {
                    $accelPath = str_replace($diskPath, $nginxPath, $localPath);

                    return $response->withHeader('Content-Type', $fileMeta->mimeType() ?? '')
                        ->withHeader('X-Accel-Redirect', $accelPath);
                }
            }
        }

        return $response->withFile($filesystem->readStream($path), $fileMeta->mimeType() ?? '');
    }
}
