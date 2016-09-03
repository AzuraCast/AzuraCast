<?php
/**
 * App Core Framework Version
 **/

namespace App;

define('APP_CORE_VERSION', '20160903');
define('APP_CORE_RELEASE', 'Pre-Alpha');

class Version
{
    public static function getVersion()
    {
        return APP_CORE_VERSION;
    }

    public static function getVersionText()
    {
        return 'v'.APP_CORE_VERSION.' '.APP_CORE_RELEASE;
    }

}