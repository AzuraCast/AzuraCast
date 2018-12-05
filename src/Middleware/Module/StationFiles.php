<?php
namespace App\Middleware\Module;

use App\Radio\Backend\BackendAbstract;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

/**
 * Module middleware for the file management pages.
 */
class StationFiles
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $backend = $request->getStationBackend();

        if (!$backend::supportsMedia()) {
            throw new \Azura\Exception(__('This feature is not currently supported on this station.'));
        }

        $file = $request->getParam('file', '');
        $file_path = 'media://'.$file;

        $request = $request->withAttribute('file', $file)
            ->withAttribute('file_path', $file_path);

        return $next($request, $response);
    }


}
