<?php
namespace App\Service;

class Notifico
{
    /**
     * Post a message to the Notifico IRC notification service.
     *
     * @param $payload
     * @return null|string
     */
    public static function post($payload)
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->get('config');

        $push_url = $config->apis->notifico_push_url;

        if ($push_url)
        {
            $client = new \GuzzleHttp\Client;
            $response = $client->post($push_url, ['body' => ['payload' => $payload]]);

            return $response->getBody()->getContents();
        }

        return null;
    }
}