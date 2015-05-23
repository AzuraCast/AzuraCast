<?php
namespace PVL\Service;

use Aws\Common\Facade\S3;
use Aws\S3\S3Client;

class AmazonS3
{
    protected static $bucket;
    protected static $client;

    /**
     * Upload (move) a local file to the S3 service, or to a local fallback URL.
     *
     * @param $local_file_path
     * @param $remote_file_stub
     * @throws \DF\Exception
     */
    public static function upload($local_file_path, $remote_file_stub)
    {
        if (!file_exists($local_file_path))
            throw new \DF\Exception('Local file path "'.$local_file_path.'" does not exist.');

        $remote_path = self::path($remote_file_stub);
        @copy($local_file_path, $remote_path, stream_context_create([
            's3' => [
                'ACL'        => 'public-read',
            ],
        ]));
        @unlink($local_file_path);
    }

    /**
     * Download (copy to local) a file from the S3 repository.
     *
     * @param $remote_file_stub
     * @param $local_file_path
     */
    public static function download($remote_file_stub, $local_file_path)
    {
        if (file_exists($local_file_path))
            @unlink($local_file_path);

        $remote_path = self::path($remote_file_stub);
        @copy($remote_path, $local_file_path);
    }

    /**
     * Delete a specified file.
     *
     * @param $remote_file_stub
     */
    public static function delete($remote_file_stub)
    {
        $remote_path = self::path($remote_file_stub);
        @unlink($remote_path);
    }

    /**
     * Return the remote URL of the file (or local fallback).
     *
     * @param $remote_file_path
     * @return string
     */
    public static function url($remote_file_path)
    {
        if (self::isEnabled())
            return DF_UPLOAD_URL.'/'.$remote_file_path;
        else
            return \DF\Url::content($remote_file_path);
    }

    /**
     * Get the S3 stream wrapper path of a given file, or local fallback.
     *
     * @param $remote_file_path
     * @return string
     */
    public static function path($remote_file_path)
    {
        if (self::isEnabled())
            return 's3://'.self::getBucket().'/'.ltrim($remote_file_path, '/');
        else
            return DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.ltrim($remote_file_path, '/');
    }

    /**
     * Check if S3 service is enabled and configured properly.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        // Secondary upload domain must be specified.
        if (!defined('DF_UPLOAD_URL'))
            return false;

        // Check that S3 configuration is successful.
        $client = self::initClient();
        if (!($client instanceof S3Client))
            return false;

        return true;
    }

    /**
     * Initialize the Amazon S3 client.
     *
     * @return S3Client
     */
    public static function initClient()
    {
        if (!self::$client)
        {
            $di = self::getDi();
            $config = $di->get('config');

            if (!$config->apis->amazon_aws)
                return false;

            $creds = $config->apis->amazon_aws->toArray();

            if (empty($creds['access_key_id']))
                return false;

            self::$bucket = $creds['s3_bucket'];

            // Instantiate the S3 client with your AWS credentials
            self::$client = S3Client::factory(array(
                'credentials' => array(
                    'key'    => $creds['access_key_id'],
                    'secret' => $creds['secret_access_key'],
                )
            ));

            // Register 's3://' streams.
            self::$client->registerStreamWrapper();
        }

        return self::$client;
    }

    /**
     * Returns the current 3 bucket path.
     * @return string S3 Bucket
     */
    public static function getBucket()
    {
        return self::$bucket;
    }

    /**
     * Set a new S3 bucket path.
     * @param $new_bucket
     */
    public static function setBucket($new_bucket)
    {
        self::$bucket = $new_bucket;
    }

    /**
     * @return \Phalcon\DiInterface
     */
    protected static function getDi()
    {
        return \Phalcon\Di::getDefault();
    }
}