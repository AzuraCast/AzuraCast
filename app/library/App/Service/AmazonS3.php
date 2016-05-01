<?php
namespace App\Service;

use App\Exception;
use Aws\S3\S3Client;

class AmazonS3
{
    const DEFAULT_UPLOAD_TYPE = 'art';

    protected $_config;

    protected $_use_s3;

    protected $_client;

    public function __construct(\App\Config $config)
    {
        $this->_config = $config;

        if (!$config->apis->amazon_aws)
            $this->_use_s3 = false;
        else
            $this->_use_s3 = (strpos($config->application->art_path, 's3://') !== false);

        if ($this->_use_s3)
        {
            $creds = $config->apis->amazon_aws->toArray();

            if (empty($creds['access_key_id']))
                throw new Exception('No AWS credentials specified, but AWS URL used for uploads!');

            // Instantiate the S3 client with your AWS credentials
            $this->_client = new S3Client(array(
                'version'    => '2006-03-01',
                'region'     => 'us-east-1',
                'credentials' => array(
                    'key'    => $creds['access_key_id'],
                    'secret' => $creds['secret_access_key'],
                )
            ));

            // Register 's3://' streams.
            $this->_client->registerStreamWrapper();
        }
    }

    /**
     * Upload (move) a local file to the S3 service, or to a local fallback URL.
     *
     * @param $upload_type string art|avatars
     * @param $local_file_path
     * @param $remote_file_stub
     * @throws Exception
     */
    public function upload($local_file_path, $remote_path)
    {
        $this->copy($local_file_path, $remote_path);
        @unlink($local_file_path);
    }

    /**
     * Copy a local file to the S3 service.
     *
     * @param $local_file_path
     * @param $remote_path
     * @throws \App\Exception
     */
    public function copy($local_file_path, $remote_path)
    {
        if (!file_exists($local_file_path))
            throw new Exception('Local file path "'.$local_file_path.'" does not exist.');

        @copy($local_file_path, $remote_path, stream_context_create([
            's3' => [
                'ACL'        => 'public-read',
            ],
        ]));
    }

    /**
     * Download (copy to local) a file from the S3 repository.
     *
     * @param $remote_file_stub
     * @param $local_file_path
     */
    public function download($remote_path, $local_file_path)
    {
        if (file_exists($local_file_path))
            @unlink($local_file_path);

        @copy($remote_path, $local_file_path);
    }

    /**
     * Delete a specified file.
     *
     * @param $remote_file_stub
     */
    public function delete($remote_file_path)
    {
        $remote_path = $this->path($remote_file_path);
        @unlink($remote_path);
    }

    /**
     * Return the remote URL of the file (or local fallback).
     *
     * @param $remote_file_path
     * @return string
     */
    public function url($remote_file_path, $upload_type = self::DEFAULT_UPLOAD_TYPE)
    {
        $base_url = $this->_config->application->{$upload_type.'_url'};
        return $base_url.'/'.$remote_file_path;
    }

    /**
     * Get the S3 stream wrapper path of a given file, or local fallback.
     *
     * @param $remote_file_path
     * @return string
     */
    public function path($remote_file_path, $upload_type = self::DEFAULT_UPLOAD_TYPE)
    {
        $base_dir = $this->_config->application->{$upload_type.'_path'};

        // Create the path if not using S3 uploads.
        if (!$this->_use_s3 && !is_dir($base_dir))
            @mkdir($base_dir, 0777, true);

        return $base_dir.'/'.$remote_file_path;
    }
}