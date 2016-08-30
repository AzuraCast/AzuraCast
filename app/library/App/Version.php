<?php
/**
 * App Core Framework Version
 **/

namespace App;

define('APP_CORE_VERSION', 'Pre-Alpha');

class Version
{
    public static function getVersion()
    {
        return APP_CORE_VERSION;
    }
}