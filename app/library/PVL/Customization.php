<?php
namespace PVL;

class Customization
{
    /**
     * Comprehensive functions to handle both custom and default values.
     */

    public static function get($key)
    {
        if (!DF_IS_COMMAND_LINE)
        {
            $custom_value = self::getCustom($key);

            if ($custom_value !== NULL)
                return $custom_value;
        }

        return self::getDefault($key);
    }

    public static function set($key, $value)
    {
        self::setCustom($key, $value);
    }

    /**
     * Look for user-customized values (in session or user profile).
     */

    public static function getCustom($key)
    {
        // Check for session variable.
        $session = self::getSession();
        if ($session->$key)
            return $session->$key;

        // Check for permanent variable if logged in.
        $auth = \Phalcon\DI::getDefault()->get('auth');
        if ($auth->isLoggedIn())
        {
            $user = $auth->getLoggedInUser();
            $custom_options = (array)$user->customization;

            if (isset($custom_options[$key])) {
                $session->$key = $custom_options[$key];
                return $custom_options[$key];
            }
        }

        return NULL;
    }

    public static function setCustom($key, $new_value)
    {
        // Set session regardless of logged-in status.
        $session = self::getSession();
        $session->$key = $new_value;

        // Set permanent record if logged in.
        $auth = \Phalcon\DI::getDefault()->get('auth');
        if ($auth->isLoggedIn())
        {
            $user = $auth->getLoggedInUser();

            $custom_options = (array)$user->customization;

            if ($new_value !== NULL)
                $custom_options[$key] = $new_value;
            else
                unset($custom_options[$key]);

            $user->customization = $custom_options;
            $user->save();
        }
    }

    public static function getSession()
    {
        return \DF\Session::getNamespace('customization');
    }

    /**
     * Defaults
     */

    public static function getDefault($key)
    {
        $defaults = self::getDefaults();
        return $defaults[$key];
    }

    public static function getDefaults()
    {
        static $defaults;
        if (!$defaults)
        {
            $config = \Phalcon\DI::getDefault()->get('config');
            $defaults = $config->pvl->customization_defaults->toArray();
        }
        return $defaults;
    }
}