<?php
namespace App\Middleware;

use App\Exception\PermissionDenied;
use App\Http\RequestHelper;
use App\Acl;
use App\Entity;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class Permissions
{
    /** @var Acl */
    protected $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @param string $action
     * @param bool $use_station
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        string $action,
        bool $use_station = false
    ): ResponseInterface {
        if ($use_station) {
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

            if (!$this->acl->userAllowed($user, $action, $station_id)) {
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
