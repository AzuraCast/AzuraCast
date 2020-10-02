<?php
namespace App\Flysystem;

use App\Exception;

class StationFilesystem extends FilesystemGroup
{
    /**
     * Copy a file from the specified path to the temp directory
     *
     * @param string $from The permanent path to copy from
     * @param string|null $to The temporary path to copy to (temp://original if not specified)
     *
     * @return string The temporary path
     */
    public function copyToTemp($from, $to = null): string
    {
        [, $path_from] = $this->getPrefixAndPath($from);

        if (null === $to) {
            $random_prefix = substr(md5(random_bytes(8)), 0, 5);
            $to = Filesystem::PREFIX_TEMP . '://' . $random_prefix . '_' . $path_from;
        }

        if ($this->has($to)) {
            $this->delete($to);
        }

        $this->copy($from, $to);

        return $to;
    }

    /**
     * Update the value of a permanent file from a temporary directory.
     *
     * @param string $from The temporary path to update from
     * @param string $to The permanent path to update to
     * @param array $config
     *
     * @return string
     */
    public function updateFromTemp($from, $to, array $config = []): string
    {
        $buffer = $this->readStream($from);
        if ($buffer === false) {
            throw new Exception('Source file could not be read.');
        }

        $written = $this->putStream($to, $buffer, $config);

        if (is_resource($buffer)) {
            fclose($buffer);
        }

        if ($written) {
            $this->delete($from);
        }

        return $to;
    }
}
