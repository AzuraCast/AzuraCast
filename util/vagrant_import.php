<?php
/**
 * Vagrant Remote API importer script.
 */

use \Entity\User;
use \Entity\Role;

require_once dirname(__FILE__) . '/../app/bootstrap.php';

set_time_limit(0);
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

$config = $di->get('config');

$api_key = $config->apis->pvl_api_key;
$remote_base = 'http://api.ponyvillelive.com';

if (empty($api_key))
    die('No API key specified in app/config/apis.conf.php. Please contact PVL developer team!');

/**
 * Database Import
 */

echo 'Importing database entries from production server...'.PHP_EOL;

$remote_url = $remote_base.'/dev/import?key='.$api_key;
$remote_response_raw = file_get_contents($remote_url);

$remote_response = @json_decode($remote_response_raw, true);

if ($remote_response['status'] != 'success')
    die('The remote server could not return a valid MySQL import response. Halting remote import.');

$db_path = $remote_response['result']['path'];

// Force S3 enabled in development mode.
define('DF_UPLOAD_URL', 'dev.pvlive.me');

$s3_client = \PVL\Service\AmazonS3::initClient();
$s3_bucket = \PVL\Service\AmazonS3::getBucket();

if (!$s3_client)
    die('Amazon S3 could not be initialized! Halting remote import.');

// Trigger download of the entire bucket to the local static folder.
$s3_client->downloadBucket(DF_INCLUDE_STATIC, $s3_bucket);

// Clean up S3 bucket.
$remote_url = $remote_base.'/dev/cleanup?key='.$api_key;

// Prepare and execute mysqlimport command.
$db_path_full = DF_INCLUDE_STATIC.DIRECTORY_SEPARATOR.$db_path;

$db_config = $config->db->toArray();
$command_flags = array(
    '-h '.$db_config['host'],
    '-u '.$db_config['user'],
    '-p'.$db_config['password'],
    $db_config['dbname']
);
$command = 'mysql '.implode(' ', $command_flags).' < '.$db_path_full;

system($command);

@unlink($db_path_full);
@rmdir(dirname($db_path_full));

// Create initial user account.
$user = new User;
$user->email = 'admin@ponyvillelive.com';
$user->setAuthPassword('password');
$user->name = 'Administrator';

$role = Role::find(1);

if ($role instanceof Role)
{
    $user->roles->add($role);
    $user->save();
}

echo 'Database and Amazon S3 import complete.'.PHP_EOL;
exit;