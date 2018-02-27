<?php
namespace App\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Log\ResponseWriter;
use Monolog;

/**
 * Echo debug log messages out to the response.
 */
class DebugEcho
{
    /** @var Monolog\Logger */
    protected $logger;

    public function __construct(Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $handler = new ResponseWriter($response);
        $handler->setFormatter(new \App\Log\DebugHtml());

        $this->logger->pushHandler($handler);

        return $next($request, $response);
    }
}