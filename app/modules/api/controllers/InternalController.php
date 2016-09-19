<?php
namespace Modules\Api\Controllers;

use \Entity\Station;
use Entity\StationStreamer;

class InternalController extends BaseController
{
    public function streamauthAction()
    {
        if (!$this->hasParam('id'))
            return $this->_authFail('No station specified!');

        $id = (int)$this->getParam('id');
        $station = Station::find($id);

        if (!($station instanceof Station))
            return $this->_authFail('Invalid station specified');

        // Log requests to a temp file for debugging.
        $request_vars = "-------\n".date('F j, Y g:i:s')."\n".print_r($_REQUEST, true)."\n".print_r($this->params, true);
        $log_path = APP_INCLUDE_TEMP.'/icecast_stream_auth.txt';
        file_put_contents($log_path, $request_vars, \FILE_APPEND);

        /* Passed via POST from IceCast
         * [action] => stream_auth
         * [mount] => /radio.mp3
         * [ip] => 10.0.2.2
         * [server] => localhost
         * [port] => 8000
         * [user] => testuser
         * [pass] => testpass
         */

        if (!$station->enable_streamers)
            return $this->_authFail('Support for streamers/DJs on this station is disabled.');

        if (StationStreamer::authenticate($station, $_REQUEST['user'], $_REQUEST['pass']))
            return $this->_authSuccess();
        else
            return $this->_authFail('Could not authenticate streamer account.');
    }

    protected function _authFail($message)
    {
        $this->response->withHeader('icecast-auth-user', '0');
        $this->response->withHeader('Icecast-Auth-Message', $message);

        $this->response->getBody()->write('Authentication failure: '.$message);
        return $this->response;
    }

    protected function _authSuccess()
    {
        $this->response->withHeader('icecast-auth-user', 1);

        $this->response->getBody()->write('Success!');
        return $this->response;
    }
}