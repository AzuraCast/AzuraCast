<?php
namespace App\Service;

use PVL\Debug;
use PVL\Utilities;

class Curl
{
    /**
     * Submit a URL request with a specified cache lifetime.
     *
     * @param null $c_opts
     * @param int $cache_time
     * @return string
     */
    public static function request($c_opts = null)
    {
        // Compose cURL configuration array.
        if (is_null($c_opts))
            $c_opts = array();
        elseif (!is_array($c_opts))
            $c_opts = array('url' => $c_opts);

        $c_defaults = array(
            'method'    => 'GET',
            'useragent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2',
            'timeout'   => 10,
        );
        $c_opts = array_merge($c_defaults, $c_opts);

        Debug::log('cURL Outgoing Request: '.$c_opts['url']);
        Debug::startTimer('Make cURL Request');

        $postfields = false;
        if (!empty($c_opts['params']))
        {
            if (strtoupper($c_opts['method']) == 'POST')
                $postfields = $c_opts['params'];
            else
                $c_opts['url'] = $c_opts['url'].'?'.http_build_query($c_opts['params']);
        }

        // Start cURL request.
        $curl = curl_init($c_opts['url']);

        // Handle POST support.
        if (strtoupper($c_opts['method']) == 'POST')
            curl_setopt($curl, CURLOPT_POST, true);

        if (!empty($c_opts['referer']))
            curl_setopt($curl, CURLOPT_REFERER, $c_opts['referer']);

        if ($postfields)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $c_opts['timeout']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $c_opts['timeout']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $c_opts['useragent']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);

        // Custom DNS management.
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 600);

        // Set custom HTTP headers.
        if (!empty($c_opts['headers']))
            curl_setopt($curl, CURLOPT_HTTPHEADER, $c_opts['headers']);

        $return_raw = Utilities::curl_exec_utf8($curl);
        // End cURL request.

        Debug::endTimer('Make cURL Request');

        // Log more detailed information to screen about resolution times.
        $conn_info = curl_getinfo($curl);

        $important_conn_info = array('url', 'http_code', 'total_time', 'namelookup_time', 'connect_time', 'pretransfer_time', 'starttransfer_time', 'redirect_time');
        $debug_conn_info = array();

        foreach($important_conn_info as $conn_param)
            $debug_conn_info[$conn_param] = $conn_info[$conn_param];

        Debug::print_r($debug_conn_info);

        $error = curl_error($curl);
        if ($error)
            Debug::log("Curl error: ".$error);

        return trim($return_raw);
    }
}