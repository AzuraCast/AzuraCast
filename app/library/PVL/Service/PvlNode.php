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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

        var_dump(curl_error($ch));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Get the number of active connections on the PVLNode service.
     *
     * @return int
     * @throws \Zend_Exception
     */
    public static function getActive()
    {
        $config = \Zend_Registry::get('config');
        $url = $config->apis->pvlnode_local_url;

        // Send standard HTTP GET request.
        $connections_raw = file_get_contents($url);
        return (int)$connections_raw;
    }
}