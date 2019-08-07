<?php
namespace App\Middleware;

use App\Entity;
use App\Exception\PermissionDenied;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class Permissions implements MiddlewareInterface
{
    /** @var string */
    protected $action;

    /** @var bool */
    protected $use_station;

    public function __construct(
        string $action,
        bool $use_station = false
    ) {
        $this->action = $action;
        $this->use_station = $use_station;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->use_station) {
            $station = RequestHelper::getStation($request);
            $station_id = $station->getId();
        } else {
            $station_id = null;
        }

        try {
            try {
                $user = RequestHelper::getUser($request);
            } catch (\Exception $e) {
                throw new PermissionDenied;
            }

            $acl = RequestHelper::getAcl($request);
            if (!$acl->userAllowed($user, $this->action, $station_id)) {
                throw new PermissionDenied;
            }
        } catch (PermissionDenied $e) {
            if (RequestHelper::isApiCall($request)) {
                return ResponseHelper::withJson(
                    new \Slim\Psr7\Response(403),
                    new Entity\Api\Error(403, $e->getMessage(), $e->getFormattedMessage())
                );
            }

            throw $e;
        }

        return $handler->handle($request);
    }
}
