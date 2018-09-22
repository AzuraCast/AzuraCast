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
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend::supportsMedia()) {
            throw new \App\Exception(__('This feature is not currently supported on this station.'));
        }

        $base_dir = $station->getRadioMediaDir();

        $file = $request->getParam('file', '');
        $file_path = realpath($base_dir . '/' . $file);

        if ($file_path === false) {
            return $response->withStatus(404)
                ->withJson(['error' => ['code' => 404, 'msg' => 'File or Directory Not Found']]);
        }

        // Sanity check that the final file path is still within the base directory
        if (substr($file_path, 0, strlen($base_dir)) !== $base_dir) {
            return $response->withStatus(403)
                ->withJson(['error' => ['code' => 403, 'msg' => 'Forbidden']]);
        }

        $request = $request->withAttribute('file', $file)
            ->withAttribute('file_path', $file_path)
            ->withAttribute('base_dir', $base_dir);

        return $next($request, $response);
    }


}
