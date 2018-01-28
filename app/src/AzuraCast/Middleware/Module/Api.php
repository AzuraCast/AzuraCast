<?php
namespace AzuraCast\Middleware\Module;

use App\Session;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Handle API calls and wrap exceptions in JSON formatting.
 */
class Api
{
    /** @var Session */
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        if (!$this->session->exists()) {
            $this->session->disable();
        }

        $response = $response->withHeader('Cache-Control', 'public, max-age=' . 30)
            ->withHeader('X-Accel-Expires', 30) // CloudFlare caching
            ->withHeader('Access-Control-Allow-Origin', '*');

        // Custom error handling for API responses.
        try {
            return $next($request, $response);
        } catch(\Exception $e) {
            $return_data = [
                'type' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            if (!APP_IN_PRODUCTION) {
                $return_data['stack_trace'] = $e->getTrace();
            }

            return $response->withStatus(500)->write(json_encode($return_data));
        }
    }
}