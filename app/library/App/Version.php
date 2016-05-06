<?php
/**
 * App Core Framework Version
 **/

namespace App;

define('APP_CORE_VERSION', 'APP_PHAL_D2_201505');

class Version
{
    public static function getVersion()
    {
        return APP_CORE_VERSION;
    }
}