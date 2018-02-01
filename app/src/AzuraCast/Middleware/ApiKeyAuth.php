<?php
namespace AzuraCast\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Authenticate a specified API key
 */
class ApiKeyAuth
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
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
}