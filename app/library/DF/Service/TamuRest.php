<?php
/**
 * TAMU REST Web Services Connector
 */

namespace DF\Service;

class TamuRest
{   
    public static function getByUin($uin)
    {
        return self::request('uin', $uin);
    }
    public function getByNetid($netid)
    {
        return self::request('netid', $netid);
    }
    public function getByUid($uid)
    {
        return self::request('uid', $uid);
    }
    
    protected static function request($criteria, $value)
    {
        if (DF_APPLICATION_ENV == "standalone")
            return NULL;
        
        $settings = self::getSettings();
        
        $replacement_pattern = array(
            '{CRITERIA}'    => urlencode($criteria),
            '{VALUE}'       => urlencode($value),
            '{FORMAT}'      => 'json',
        );
        
        $uri_base = $settings['uri_base'];
        $uri_page = str_replace(array_keys($replacement_pattern), array_values($replacement_pattern), $settings['uri_style']);
        
        $http_client = new \Zend_Http_Client();
        $http_client->setUri($uri_base.$uri_page);
        
        $date_header = gmdate('D, d M Y H:i:s \G\M\T'); // Valid HTTP header date format.
        $authentication_string = $uri_page."\n".$date_header."\n".$settings['identifier'];
        $signature = base64_encode(hash_hmac('sha256', $authentication_string, $settings['shared_secret'], TRUE));
        
        $http_client->setHeaders(array(
            'Date'      => $date_header,
            'Authorization' => 'TAM '.$settings['identifier'].':'.$signature
        ));
        
        try
        {
      $response = $http_client->request('GET');
    }
    catch(\Exception $e)
    {
      return NULL;
    }
        
        if ($response->isSuccessful())
        {
            $body = $response->getBody();
            return \Zend_Json::decode($body);
        }
        else
        {
            return NULL;
        }
    }
    
    protected static function getSettings()
    {
        static $settings;
        if (!$settings)
        {
            $config = \Zend_Registry::get('config');
            $settings = $config->services->tamurest->toArray();
        }
        return $settings;
    }
}