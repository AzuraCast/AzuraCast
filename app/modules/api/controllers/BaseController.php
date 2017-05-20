<?php
namespace Controller\Api;

use Entity;

class BaseController extends \AzuraCast\Mvc\Controller
{
    public function permissions()
    {
        return true;
    }

    protected $_time_start;

    public function preDispatch()
    {
        parent::preDispatch();

        // Disable session creation.
        $session = $this->di->get('session');

        if (!$session->exists()) {
            $session->disable();
        }

        // Disable rendering.
        $this->doNotRender();

        // Allow AJAX retrieval.
        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');;

        $this->_time_start = microtime(true);

        // Set all API calls to be public cache-controlled by default.
        $this->setCachePrivacy('public');
        $this->setCacheLifetime(30);
    }

    /**
     * Authentication
     */

    /**
     * Require that an API key be supplied by the requesting user.
     *
     * @throws \App\Exception\PermissionDenied
     */
    public function requireKey()
    {
        if (!$this->authenticate()) {
            throw new \App\Exception\PermissionDenied('No valid API key specified.');
        }
    }

    /**
     * Check that the API key supplied by the requesting user is valid.
     *
     * @return bool
     */
    public function authenticate()
    {
        if (isset($_SERVER['X-API-Key'])) {
            $key = $_SERVER['X-API-Key'];
        } elseif ($this->hasParam('key')) {
            $key = $this->getParam('key');
        } else {
            return false;
        }

        if (empty($key)) {
            return false;
        }

        $record = $this->em->getRepository(Entity\ApiKey::class)->find($key);

        if ($record instanceof Entity\ApiKey) {
            $record->calls_made++;

            $this->em->persist($record);
            $this->em->flush();
            return true;
        }

        return false;
    }

    /*
     * Common Functions
     */

    /**
     * Retrieve a station from the specified parameters, if possible.
     *
     * @param bool $required
     * @return Entity\Station|null
     * @throws \Exception
     */
    protected function getStation($required = true)
    {
        $id = $this->getParam('station');

        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        if (is_numeric($id)) {
            $record = $station_repo->find($id);
        } else {
            $record = $station_repo->findByShortCode($id);
        }

        if ($required && !($record instanceof Entity\Station)) {
            throw new \Exception('Station not found!');
        }

        return $record;
    }

    /**
     * @param Entity\Station $station
     * @param $permission_name
     * @return bool
     * @throws \App\Exception\PermissionDenied
     */
    public function checkStationPermission(Entity\Station $station, $permission_name) {
        if ($this->authenticate()) {
            return true;
        }

        if (!$this->acl->isAllowed($permission_name, $station->id)) {
            throw new \App\Exception\PermissionDenied('Permission denied');
        }

        return true;
    }

    /**
     * Result Printout
     */

    public function returnSuccess($data)
    {
        return $this->returnToScreen([
            'status' => 'success',
            'result' => $data,
        ]);
    }

    public function returnError($message, $error_code = 400)
    {
        $this->response = $this->response->withStatus($error_code);

        return $this->returnToScreen([
            'status' => 'error',
            'error' => $message,
        ]);
    }

    public function returnToScreen($obj)
    {
        $format = strtolower($this->getParam('format', 'json'));

        if ($format == 'xml') {
            return $this->returnRaw(\App\Export::array_to_xml($obj), 'xml');
        } else {
            return $this->returnRaw(json_encode($obj, \JSON_UNESCAPED_SLASHES), 'json');
        }
    }

    public function returnRaw($message, $format = 'json')
    {
        $content_type = ($format == 'xml') ? 'text/xml' : 'application/json';
        $this->response = $this->response->withHeader('Content-Type', $content_type);

        $this->response->getBody()->write($message);

        return $this->response;
    }
}