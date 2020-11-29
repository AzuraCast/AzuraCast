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

namespace App\Service;

use App\Exception;
use App\File;
use App\Http\Response;
use App\Http\ServerRequest;
use Normalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;
use const SCANDIR_SORT_NONE;

class Flow
{
    /**
     * Process the request and return a response if necessary, or the completed file details if successful.
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param string|null $temp_dir
     *
     * @return mixed[]|ResponseInterface|null
     */
    public static function process(
        ServerRequest $request,
        Response $response,
        string $temp_dir = null
    ) {
        if (null === $temp_dir) {
            $temp_dir = sys_get_temp_dir() . '/uploads/';
        }

        $params = $request->getParams();

        $flowIdentifier = $params['flowIdentifier'] ?? '';
        $flowChunkNumber = (int)($params['flowChunkNumber'] ?? 1);
        $flowFilename = $params['flowFilename'] ?? ($flowIdentifier ?: 'upload-' . date('Ymd'));

        // init the destination file (format <filename.ext>.part<#chunk>
        $chunkBaseDir = $temp_dir . '/' . $flowIdentifier;
        $chunkPath = $chunkBaseDir . '/' . $flowIdentifier . '.part' . $flowChunkNumber;

        $currentChunkSize = (int)($params['flowCurrentChunkSize'] ?? 0);

        $targetSize = (int)($params['flowTotalSize'] ?? 0);
        $targetChunks = (int)($params['flowTotalChunks'] ?? 0);

        // Check if request is GET and the requested chunk exists or not. This makes testChunks work
        if ('GET' === $request->getMethod()) {
            // Force a reupload of the last chunk if all chunks are uploaded, to trigger processing below.
            if (
                $flowChunkNumber !== $targetChunks
                && file_exists($chunkPath)
                && filesize($chunkPath) === $currentChunkSize
            ) {
                return $response->withStatus(200, 'OK');
            }

            return $response->withStatus(204, 'No Content');
        }

        $files = $request->getUploadedFiles();

        if (!empty($files)) {
            foreach ($files as $file) {
                /** @var UploadedFileInterface $file */
                if ($file->getError() !== UPLOAD_ERR_OK) {
                    throw new Exception('Error ' . $file->getError() . ' in file ' . $flowFilename);
                }

                // the file is stored in a temporary directory
                if (!is_dir($chunkBaseDir)) {
                    if (!mkdir($chunkBaseDir, 0777, true) && !is_dir($chunkBaseDir)) {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $chunkBaseDir));
                    }
                }

                if ($file->getSize() !== $currentChunkSize) {
                    throw new Exception(sprintf(
                        'File size of %s does not match expected size of %s',
                        $file->getSize(),
                        $currentChunkSize
                    ));
                }

                $file->moveTo($chunkPath);
            }

            if (self::allPartsExist($chunkBaseDir, $targetSize, $targetChunks)) {
                return self::createFileFromChunks(
                    $temp_dir,
                    $chunkBaseDir,
                    $flowIdentifier,
                    $flowFilename,
                    $targetChunks
                );
            }

            // Return an OK status to indicate that the chunk upload itself succeeded.
            return $response->withStatus(200, 'OK');
        }

        return null;
    }

    /**
     * Check if all parts exist and are uploaded.
     *
     * @param string $chunkBaseDir
     * @param int $targetSize
     * @param int $targetChunkNumber
     */
    protected static function allPartsExist(
        string $chunkBaseDir,
        int $targetSize,
        int $targetChunkNumber
    ): bool {
        $chunkSize = 0;
        $chunkNumber = 0;

        foreach (array_diff(scandir($chunkBaseDir, SCANDIR_SORT_NONE), ['.', '..']) as $file) {
            $chunkSize += filesize($chunkBaseDir . '/' . $file);
            $chunkNumber++;
        }

        return ($chunkSize === $targetSize && $chunkNumber === $targetChunkNumber);
    }

    /**
     * Reassemble the file on the local destination disk and return the relevant information.
     *
     * @param string $tempDir
     * @param string $chunkBaseDir
     * @param string $chunkIdentifier
     * @param string $originalFileName
     * @param int $numChunks
     *
     * @return mixed[]
     */
    protected static function createFileFromChunks(
        string $tempDir,
        string $chunkBaseDir,
        string $chunkIdentifier,
        string $originalFileName,
        int $numChunks
    ): array {
        $originalFileName = basename($originalFileName);
        $originalFileName = Normalizer::normalize($originalFileName, Normalizer::FORM_KD);
        $originalFileName = File::sanitizeFileName($originalFileName);

        // Truncate filenames whose lengths are longer than 255 characters, while preserving extension.
        if (strlen($originalFileName) > 255) {
            $ext = pathinfo($originalFileName, PATHINFO_EXTENSION);
            $fileName = pathinfo($originalFileName, PATHINFO_FILENAME);
            $fileName = substr($fileName, 0, 255 - 1 - strlen($ext));
            $originalFileName = $fileName . '.' . $ext;
        }

        $finalPath = $tempDir . '/' . $originalFileName;

        $fp = fopen($finalPath, 'wb+');

        for ($i = 1; $i <= $numChunks; $i++) {
            fwrite($fp, file_get_contents($chunkBaseDir . '/' . $chunkIdentifier . '.part' . $i));
        }

        fclose($fp);

        // rename the temporary directory (to avoid access from other
        // concurrent chunk uploads) and then delete it.
        if (rename($chunkBaseDir, $chunkBaseDir . '_UNUSED')) {
            self::rrmdir($chunkBaseDir . '_UNUSED');
        } else {
            self::rrmdir($chunkBaseDir);
        }

        return [
            'path' => $finalPath,
            'filename' => $originalFileName,
            'size' => filesize($finalPath),
        ];
    }

    /**
     * Delete a directory RECURSIVELY
     *
     * @param string $dir - directory path
     *
     * @link http://php.net/manual/en/function.rmdir.php
     */
    protected static function rrmdir($dir): void
    {
        if (is_dir($dir)) {
            $objects = array_diff(scandir($dir, SCANDIR_SORT_NONE), ['.', '..']);
            foreach ($objects as $object) {
                if (is_dir($dir . '/' . $object)) {
                    self::rrmdir($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
