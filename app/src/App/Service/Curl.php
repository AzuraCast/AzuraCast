<?php
namespace App\Service;

use App\Debug;

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
        if (is_null($c_opts)) {
            $c_opts = [];
        } elseif (!is_array($c_opts)) {
            $c_opts = ['url' => $c_opts];
        }

        $c_defaults = [
            'method' => 'GET',
            'useragent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2',
            'timeout' => 10,
        ];
        $c_opts = array_merge($c_defaults, $c_opts);

        $postfields = false;
        if (!empty($c_opts['params'])) {
            if (strtoupper($c_opts['method']) == 'POST') {
                $postfields = $c_opts['params'];
            } else {
                $c_opts['url'] = $c_opts['url'] . '?' . http_build_query($c_opts['params']);
            }
        }

        // Start cURL request.
        $curl = curl_init($c_opts['url']);

        // Handle POST support.
        if (strtoupper($c_opts['method']) == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        }

        if (!empty($c_opts['referer'])) {
            curl_setopt($curl, CURLOPT_REFERER, $c_opts['referer']);
        }

        if ($postfields) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $c_opts['timeout']);
        curl_setopt($curl, CURLOPT_TIMEOUT, $c_opts['timeout']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $c_opts['useragent']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);

        if (!empty($c_opts['basic_auth'])) {
            curl_setopt($curl, CURLOPT_USERPWD, $c_opts['basic_auth']);
        }

        // Custom DNS management.
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 600);

        // Set custom HTTP headers.
        if (!empty($c_opts['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $c_opts['headers']);
        }

        $return_raw = self::curl_exec_utf8($curl);
        // End cURL request.

        // Log more detailed information to screen about resolution times.
        $conn_info = curl_getinfo($curl);

        $important_conn_info = [
            'url',
            'http_code',
            'total_time',
            'namelookup_time',
            'connect_time',
            'pretransfer_time',
            'starttransfer_time',
            'redirect_time'
        ];
        $debug_conn_info = [];

        foreach ($important_conn_info as $conn_param) {
            $debug_conn_info[$conn_param] = $conn_info[$conn_param];
        }

        $error = curl_error($curl);

        return trim($return_raw);
    }

    public static function curl_exec_utf8($ch)
    {
        $data = curl_exec($ch);
        if (!is_string($data)) {
            return $data;
        }

        unset($charset);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        /* 1: HTTP Content-Type: header */
        preg_match('@([\w/+]+)(;\s*charset=(\S+))?@i', $content_type, $matches);
        if (isset($matches[3])) {
            $charset = $matches[3];
        }

        /* 2: <meta> element in the page */
        if (!isset($charset)) {
            preg_match('@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s*charset=([^\s"]+))?@i', $data,
                $matches);
            if (isset($matches[3])) {
                $charset = $matches[3];
            }
        }

        /* 3: <xml> element in the page */
        if (!isset($charset)) {
            preg_match('@<\?xml.+encoding="([^\s"]+)@si', $data, $matches);
            if (isset($matches[1])) {
                $charset = $matches[1];
            }
        }

        /* 4: PHP's heuristic detection */
        if (!isset($charset)) {
            $encoding = mb_detect_encoding($data);
            if ($encoding) {
                $charset = $encoding;
            }
        }

        /* 5: Default for HTML */
        if (!isset($charset)) {
            if (strstr($content_type, "text/html") === 0) {
                $charset = "ISO 8859-1";
            }
        }

        /* Convert it if it is anything but UTF-8 */
        if (isset($charset) && strtoupper($charset) != "UTF-8") {
            $data = iconv($charset, 'UTF-8//IGNORE', $data);
        }

        return $data;
    }
}