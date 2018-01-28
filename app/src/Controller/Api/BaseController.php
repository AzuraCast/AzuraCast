<?php
namespace Controller\Api;

use Entity;
use Slim\Http\Request;
use Slim\Http\Response;

class BaseController extends \AzuraCast\Mvc\Controller
{
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
            $record->callMade();

            $this->em->persist($record);
            $this->em->flush();
            return true;
        }

        return false;
    }

    /**
     * Result Printout
     */

    public function returnSuccess(Response $response, $data): Response
    {
        return $this->returnToScreen($response, $data);
    }

    public function returnError(Response $response, $message, $error_code = 400): Response
    {
        $response = $response->withStatus($error_code);
        return $this->returnToScreen($response, $message);
    }

    public function returnToScreen(Response $response, $body): Response
    {
        return $response->withHeader('Content-Type', 'application/json')
            ->write(json_encode($body, \JSON_UNESCAPED_SLASHES));
    }
}