<?php

declare(strict_types=1);

namespace App\Http;

use App\Flysystem\Adapter\LocalAdapterInterface;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Nginx\CustomUrls;
use InvalidArgumentException;
use League\Flysystem\FileAttributes;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response as SlimResponse;

final class Response extends SlimResponse
{
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
     * Write a string of file data to the response as if it is a file for download.
     *
     * @param string $fileData
     * @param string $contentType
     * @param string|null $fileName
     *
     * @return static
     */
    public function renderStringAsFile(string $fileData, string $contentType, ?string $fileName = null): Response
    {
        $response = $this->response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', $contentType);

        if ($fileName !== null) {
            $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            $response = $response->withHeader('Content-Disposition', 'inline');
        }

        $response->getBody()->write($fileData);

        return new Response($response, $this->streamFactory);
    }

    public function streamFilesystemFile(
        ExtendedFilesystemInterface $filesystem,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment',
        bool $useXAccelRedirect = true
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
            ->withHeader('Content-Length', (string)$fileMeta->fileSize());

        if ($useXAccelRedirect) {
            $adapter = $filesystem->getAdapter();
            if ($adapter instanceof LocalAdapterInterface) {
                // Special internal nginx routes to use X-Accel-Redirect for far more performant file serving.
                $accelPath = CustomUrls::getXAccelPath($filesystem->getLocalPath($path));
                if (null !== $accelPath) {
                    return $response->withHeader('Content-Type', $fileMeta->mimeType() ?? '')
                        ->withHeader('X-Accel-Redirect', $accelPath);
                }
            }
        }

        return $response->withFile($filesystem->readStream($path), $fileMeta->mimeType() ?? true);
    }
}
