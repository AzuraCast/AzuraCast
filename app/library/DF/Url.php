<?php
namespace DF;

class Url
{
    public static function baseUrl()
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('url')->get('/');
    }

    // Return path to static content.
    public static function content($file_name = NULL)
    {
        $di = \Phalcon\Di::getDefault();
        return $di->get('url')->getStatic($file_name);
    }

    protected static function arrayToGetString(array $get, $preserve_existing_get = false)
    {
        $get_string = array();

        if($preserve_existing_get === true)
        {
            foreach( (array)$_GET as $key => $value )
            {
                $get_string[$key] = urlencode($key) . '=' . urlencode($value);
            }
        }

        foreach( (array)$get as $key => $value )
        {
            $get_string[$key] = urlencode($key) . '=' . urlencode($value);
        }

        if(count($get_string) > 0)
            $get_string = '?' . implode('&', $get_string);
        else
            $get_string = '';

        return $get_string;
    }
}