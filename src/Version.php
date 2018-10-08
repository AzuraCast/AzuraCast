<?php
namespace App;

define('APP_CORE_VERSION', '0.9.0-2018.10');
define('APP_CORE_RELEASE', 'Beta');

/**
 * App Core Framework Version
 */
class Version
{
    public static function getVersion()
    {
        return APP_CORE_VERSION;
    }

    public static function getVersionText()
    {
        return 'v' . APP_CORE_VERSION . ' ' . APP_CORE_RELEASE;
    }

}
