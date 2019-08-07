<?php
namespace App\Middleware\Module;

use App\Http\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Module middleware for the file management pages.
 */
class StationFiles implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $backend = RequestHelper::getStationBackend($request);

        if (!$backend::supportsMedia()) {
            throw new \Azura\Exception(__('This feature is not currently supported on this station.'));
        }

        $params = RequestHelper::getParams($request);
        $file = $params['file'] ?? '';
        $file_path = 'media://'.$file;

        $request = $request->withAttribute('file', $file)
            ->withAttribute('file_path', $file_path);

        return $handler->handle($request);
    }
}
