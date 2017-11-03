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

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Flow
{
    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Process the request and return a response if necessary, or the completed file details if successful.
     *
     * @return Response|array|null
     * @throws \App\Exception
     */
    public function process()
    {
        $flowIdentifier = $this->request->getParam('flowIdentifier', '');
        $flowChunkNumber = (int)$this->request->getParam('flowChunkNumber', '');
        $flowFilename = $this->request->getParam('flowFilename', $flowIdentifier ?: 'upload-'.date('Ymd'));

        // init the destination file (format <filename.ext>.part<#chunk>
        $chunkBaseDir = sys_get_temp_dir() . '/uploads/' . $flowIdentifier;
        $chunkPath = $chunkBaseDir . '/' . $flowIdentifier . '.part' . $flowChunkNumber;

        $currentChunkSize = (int)$this->request->getParam('flowCurrentChunkSize', 0);

        $targetSize = $this->request->getParam('flowTotalSize', 0);
        $targetChunks = (int)$this->request->getParam('flowTotalChunks', 0);

        // Check if request is GET and the requested chunk exists or not. This makes testChunks work
        if ($this->request->isGet()) {

            // Force a reupload of the last chunk if all chunks are uploaded, to trigger processing below.
            if ($flowChunkNumber !== $targetChunks
                && file_exists($chunkPath)
                && filesize($chunkPath) == $currentChunkSize) {
                return $this->response->withStatus(200, 'OK');
            } else {
                return $this->response->withStatus(204, 'No Content');
            }

        } else if (!empty($this->request->getUploadedFiles())) {

            $files = $this->request->getUploadedFiles();

            foreach ($files as $file) {
                /** @var UploadedFileInterface $file */
                if ($file->getError() !== UPLOAD_ERR_OK) {
                    throw new \App\Exception('Error ' . $file->getError() . ' in file ' . $flowFilename);
                }

                // the file is stored in a temporary directory
                if (!is_dir($chunkBaseDir)) {
                    @mkdir($chunkBaseDir, 0777, true);
                }

                if ($file->getSize() !== $currentChunkSize) {
                    throw new \App\Exception('File size of '.$file->getSize().' does not match expected size of '.$currentChunkSize);
                }

                $file->moveTo($chunkPath);
            }

            if ($this->_allPartsExist($chunkBaseDir, $targetSize, $targetChunks)) {
                return $this->_createFileFromChunks($chunkBaseDir, $flowIdentifier, $flowFilename, $targetChunks);
            } else {
                // Return an OK status to indicate that the chunk upload itself succeeded.
                return $this->response->withStatus(200, 'OK');
            }

        }

        return null;
    }

    /**
     * Check if all parts exist and are uploaded.
     *
     * @param $chunkBaseDir
     * @param $targetSize
     * @return bool
     */
    protected function _allPartsExist($chunkBaseDir, $targetSize, $targetChunkNumber)
    {
        $chunkSize = 0;
        $chunkNumber = 0;

        foreach (array_diff(scandir($chunkBaseDir), array('.', '..')) as $file) {
            $chunkSize += filesize($chunkBaseDir.'/'.$file);
            $chunkNumber++;
        }

        return ($chunkSize == $targetSize && $chunkNumber == $targetChunkNumber);
    }

    /**
     * Reassemble the file on the local destination disk and return the relevant information.
     *
     * @param $chunkBaseDir
     * @param $chunkIdentifier
     * @param $originalFileName
     * @param $numChunks
     * @return array
     */
    protected function _createFileFromChunks($chunkBaseDir, $chunkIdentifier, $originalFileName, $numChunks)
    {
        $finalPath = sys_get_temp_dir().'/'.$chunkIdentifier.'_complete';

        $fp = fopen($finalPath, 'w+');

        for ($i = 1; $i <= $numChunks; $i++) {
            fwrite($fp, file_get_contents($chunkBaseDir.'/'.$chunkIdentifier.'.part'.$i));
        }

        fclose($fp);

        // rename the temporary directory (to avoid access from other
        // concurrent chunks uploads) and than delete it
        if (rename($chunkBaseDir, $chunkBaseDir . '_UNUSED')) {
            $this->_rrmdir($chunkBaseDir . '_UNUSED');
        } else {
            $this->_rrmdir($chunkBaseDir);
        }

        return [
            'path'      => $finalPath,
            'filename'  => $originalFileName,
        ];
    }

    /**
     * Delete a directory RECURSIVELY
     * @param string $dir - directory path
     * @link http://php.net/manual/en/function.rmdir.php
     */
    protected function _rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = array_diff(scandir($dir), array('.', '..'));
            foreach ($objects as $object) {
                if (is_dir($dir . "/" . $object)) {
                    $this->_rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}