<?php
namespace PVL\Service;

class PvlNode
{
    /**
     * Push a message out via the PVLNode service.
     *
     * @param $event_type string The event to trigger in socket.io (i.e. nowplaying)
     * @param $event_data mixed An array of contents to send to clients.
     * @return mixed
     * @throws \Zend_Exception
     */
    public static function push($event_type, $event_data)
    {
        $config = \Zend_Registry::get('config');
        $url = $config->apis->pvlnode_local_url;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            'type' => $event_type,
            'contents' => $event_data,
        )));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if(curl_errno($ch))
            throw new \DF\Exception('cURL Error: '.curl_error($ch));

        curl_close($ch);

        return $response;
    }

    /**
     * Fetch analytics from the remote service (i.e. active connections, last update)
     *
     * @return int
     * @throws \Zend_Exception
     */
    public static function fetch()
    {
        $config = \Zend_Registry::get('config');
        $url = $config->apis->pvlnode_local_url;

        // Send standard HTTP GET request.
        $connections_raw = file_get_contents($url);
        return @json_decode($connections_raw, true);
    }
}