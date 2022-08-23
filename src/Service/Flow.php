<?php

/**
 * This is the implementation of the server side part of
 * Flow.js client script, which sends/uploads files
 * to a server in several chunks.
 *
 * The script receives the files in a standard way as if
 * the files were uploaded using standard HTML form (multipart).
 *
 * This PHP script stores all the chunks of a file in a temporary
 * directory (`temp`) with the extension `_part<#ChunkN>`. Once all
 * the parts have been uploaded, a final destination file is
 * being created from all the stored parts (appending one by one).
 *
 * @author Buster "Silver Eagle" Neece
 * @email buster@busterneece.com
 *
 * @author Gregory Chris (http://online-php.com)
 * @email www.online.php@gmail.com
 *
 * @editor Bivek Joshi (http://www.bivekjoshi.com.np)
 * @email meetbivek@gmail.com
 */

declare(strict_types=1);

namespace App\Service;

use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow\UploadedFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

use const SCANDIR_SORT_NONE;

final class Flow
{
    /**
     * Process the request and return a response if necessary, or the completed file details if successful.
     */
    public static function process(
        ServerRequest $request,
        Response $response,
        string $tempDir = null
    ): UploadedFile|ResponseInterface {
        if (null === $tempDir) {
            $tempDir = sys_get_temp_dir() . '/uploads';
            (new Filesystem())->mkdir($tempDir);
        }

        $params = $request->getParams();

        // Handle a regular file upload that isn't using flow.
        if (empty($params['flowTotalChunks']) || empty($params['flowIdentifier'])) {
            // Prompt an upload if this is indeed a mistaken Flow request.
            if ('GET' === $request->getMethod()) {
                return $response->withStatus(204, 'No Content');
            }

            return self::handleStandardUpload($request, $tempDir);
        }

        $flowIdentifier = $params['flowIdentifier'];
        $flowChunkNumber = (int)($params['flowChunkNumber'] ?? 1);

        $targetSize = (int)($params['flowTotalSize'] ?? 0);
        $targetChunks = (int)($params['flowTotalChunks']);

        $flowFilename = $params['flowFilename'] ?? ($flowIdentifier);

        // init the destination file (format <filename.ext>.part<#chunk>
        $chunkBaseDir = $tempDir . '/' . $flowIdentifier;
        $chunkPath = $chunkBaseDir . '/' . $flowIdentifier . '.part' . $flowChunkNumber;

        $currentChunkSize = (int)($params['flowCurrentChunkSize'] ?? 0);

        // Check if request is GET and the requested chunk exists or not. This makes testChunks work
        if ('GET' === $request->getMethod()) {
            // Force a reupload of the last chunk if all chunks are uploaded, to trigger processing below.
            if (
                $flowChunkNumber !== $targetChunks
                && is_file($chunkPath)
                && filesize($chunkPath) === $currentChunkSize
            ) {
                return $response->withStatus(200, 'OK');
            }

            return $response->withStatus(204, 'No Content');
        }

        $files = $request->getUploadedFiles();
        if (empty($files)) {
            throw new Exception\NoFileUploadedException();
        }

        /** @var UploadedFileInterface $file */
        $file = reset($files);

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error ' . $file->getError() . ' in file ' . $flowFilename);
        }

        // the file is stored in a temporary directory
        (new Filesystem())->mkdir($chunkBaseDir);

        if ($file->getSize() !== $currentChunkSize) {
            throw new RuntimeException(
                sprintf(
                    'File size of %s does not match expected size of %s',
                    $file->getSize(),
                    $currentChunkSize
                )
            );
        }

        $file->moveTo($chunkPath);

        clearstatcache();

        if ($flowChunkNumber === $targetChunks) {
            // Handle last chunk.
            if (self::allPartsExist($chunkBaseDir, $targetSize, $targetChunks)) {
                return self::createFileFromChunks(
                    $tempDir,
                    $chunkBaseDir,
                    $flowIdentifier,
                    $flowFilename,
                    $targetChunks
                );
            }

            // Upload succeeded, but re-trigger upload anyway for the above.
            return $response->withStatus(204, 'No Content');
        }

        // Return an OK status to indicate that the chunk upload itself succeeded.
        return $response->withStatus(200, 'OK');
    }

    private static function handleStandardUpload(
        ServerRequest $request,
        string $tempDir
    ): UploadedFile {
        $files = $request->getUploadedFiles();
        if (empty($files)) {
            throw new Exception\NoFileUploadedException();
        }

        /** @var UploadedFileInterface $file */
        $file = reset($files);

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Uploaded file error code: ' . $file->getError());
        }

        $uploadedFile = new UploadedFile($file->getClientFilename(), null, $tempDir);
        $file->moveTo($uploadedFile->getUploadedPath());

        return $uploadedFile;
    }

    /**
     * Check if all parts exist and are uploaded.
     *
     * @param string $chunkBaseDir
     * @param int $targetSize
     * @param int $targetChunkNumber
     */
    private static function allPartsExist(
        string $chunkBaseDir,
        int $targetSize,
        int $targetChunkNumber
    ): bool {
        $chunkSize = 0;
        $chunkNumber = 0;

        foreach (array_diff(scandir($chunkBaseDir, SCANDIR_SORT_NONE) ?: [], ['.', '..']) as $file) {
            $chunkSize += filesize($chunkBaseDir . '/' . $file);
            $chunkNumber++;
        }

        return ($chunkSize === $targetSize && $chunkNumber === $targetChunkNumber);
    }

    private static function createFileFromChunks(
        string $tempDir,
        string $chunkBaseDir,
        string $chunkIdentifier,
        string $originalFileName,
        int $numChunks
    ): UploadedFile {
        $uploadedFile = new UploadedFile($originalFileName, null, $tempDir);

        $finalPath = $uploadedFile->getUploadedPath();
        $fp = fopen($finalPath, 'wb+');

        if (false === $fp) {
            throw new RuntimeException(
                sprintf(
                    'Could not open final path "%s" for writing.',
                    $finalPath
                )
            );
        }

        for ($i = 1; $i <= $numChunks; $i++) {
            $chunkContents = file_get_contents($chunkBaseDir . '/' . $chunkIdentifier . '.part' . $i);
            if (empty($chunkContents)) {
                throw new RuntimeException(
                    sprintf(
                        'Could not load chunk "%d" for writing.',
                        $i
                    )
                );
            }

            fwrite($fp, $chunkContents);
        }

        fclose($fp);

        // rename the temporary directory (to avoid access from other
        // concurrent chunk uploads) and then delete it.
        (new Filesystem())->remove([
            $chunkBaseDir,
        ]);

        return $uploadedFile;
    }
}
