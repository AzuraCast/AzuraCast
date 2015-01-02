<?php
namespace PVL\Service;

class Notifico
{
    public static function post($payload)
    {
        $config = \Zend_Registry::get('config');
        $push_url = $config->apis->notifico_push_url;

        if ($push_url)
        {
            $client = new \Zend_Http_Client($push_url);
            $client->setParameterPost('payload', $payload);

            return $client->request('POST');
        }
    }
}