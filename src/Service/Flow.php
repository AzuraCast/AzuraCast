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

use App\Http\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class Flow
{
    /**
     * Process the request and return a response if necessary, or the completed file details if successful.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string|null $temp_dir
     * @return array|ResponseInterface|null
     */
    public static function process(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $temp_dir = null
    ) {
        if (null === $temp_dir) {
            $temp_dir = sys_get_temp_dir().'/uploads/';
        }

        $params = RequestHelper::getParams($request);

        $flowIdentifier = $params['flowIdentifier'] ?? '';
        $flowChunkNumber = (int)($params['flowChunkNumber'] ?? 1);
        $flowFilename = $params['flowFilename'] ?? ($flowIdentifier ?: 'upload-'.date('Ymd'));

        // init the destination file (format <filename.ext>.part<#chunk>
        $chunkBaseDir = $temp_dir . '/' . $flowIdentifier;
        $chunkPath = $chunkBaseDir . '/' . $flowIdentifier . '.part' . $flowChunkNumber;

        $currentChunkSize = (int)($params['flowCurrentChunkSize'] ?? 0);

        $targetSize = (int)($params['flowTotalSize'] ?? 0);
        $targetChunks = (int)($params['flowTotalChunks'] ?? 0);

        // Check if request is GET and the requested chunk exists or not. This makes testChunks work
        if ('GET' === $request->getMethod()) {

            // Force a reupload of the last chunk if all chunks are uploaded, to trigger processing below.
            if ($flowChunkNumber !== $targetChunks
                && file_exists($chunkPath)
                && filesize($chunkPath) === $currentChunkSize) {
                return $response->withStatus(200, 'OK');
            }

            return $response->withStatus(204, 'No Content');
        }

        $files = $request->getUploadedFiles();

        print_r($files);
        exit;

        if (!empty($files)) {
            foreach ($files as $file) {
                /** @var UploadedFileInterface $file */
                if ($file->getError() !== UPLOAD_ERR_OK) {
                    throw new \Azura\Exception('Error ' . $file->getError() . ' in file ' . $flowFilename);
                }

                // the file is stored in a temporary directory
                if (!is_dir($chunkBaseDir)) {
                    @mkdir($chunkBaseDir, 0777, true);
                }

                if ($file->getSize() !== $currentChunkSize) {
                    throw new \Azura\Exception('File size of '.$file->getSize().' does not match expected size of '.$currentChunkSize);
                }

                $file->moveTo($chunkPath);
            }

            if (self::allPartsExist($chunkBaseDir, $targetSize, $targetChunks)) {
                return self::createFileFromChunks($temp_dir, $chunkBaseDir, $flowIdentifier, $flowFilename, $targetChunks);
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
     * @return bool
     */
    protected static function allPartsExist(
        string $chunkBaseDir,
        int $targetSize,
        int $targetChunkNumber
    ): bool {
        $chunkSize = 0;
        $chunkNumber = 0;

        foreach (array_diff(scandir($chunkBaseDir, \SCANDIR_SORT_NONE), array('.', '..')) as $file) {
            $chunkSize += filesize($chunkBaseDir.'/'.$file);
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
     * @return array
     */
    protected static function createFileFromChunks(
        string $tempDir,
        string $chunkBaseDir,
        string $chunkIdentifier,
        string $originalFileName,
        int $numChunks
    ): array {
        $originalFileName = \Azura\File::sanitizeFileName(basename($originalFileName));
        $finalPath = $tempDir.'/'.$originalFileName;

        $fp = fopen($finalPath, 'w+');

        for ($i = 1; $i <= $numChunks; $i++) {
            fwrite($fp, file_get_contents($chunkBaseDir.'/'.$chunkIdentifier.'.part'.$i));
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
            'path'      => $finalPath,
            'filename'  => $originalFileName,
            'size'      => filesize($finalPath),
        ];
    }

    /**
     * Delete a directory RECURSIVELY
     * @param string $dir - directory path
     * @link http://php.net/manual/en/function.rmdir.php
     */
    protected static function rrmdir($dir): void
    {
        if (is_dir($dir)) {
            $objects = array_diff(scandir($dir, \SCANDIR_SORT_NONE), array('.', '..'));
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
