<?php
namespace AzuraCast\Middleware\Module;

use App\Csrf;
use AzuraCast\Radio\Backend\BackendAbstract;
use Entity;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Module middleware for the file management pages.
 */
class StationFiles
{
    /** @var Csrf */
    protected $csrf;

    /**
     * StationFiles constructor.
     * @param Csrf $csrf
     */
    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        if (!$backend->supportsMedia()) {
            throw new \App\Exception(_('This feature is not currently supported on this station.'));
        }

        $base_dir = $station->getRadioMediaDir();
        $view->base_dir = $base_dir;

        $file = $request->getParam('file', '');
        $file_path = realpath($base_dir . '/' . $file);

        if ($file_path === false) {
            return $response->withStatus(404)
                ->withJson(['error' => ['code' => (int)404, 'msg' => 'File or Directory Not Found']]);
        }

        // Sanity check that the final file path is still within the base directory
        if (substr($file_path, 0, strlen($base_dir)) !== $base_dir) {
            return $response->withStatus(403)
                ->withJson(['error' => ['code' => (int)403, 'msg' => 'Forbidden']]);
        }

        $request = $request->withAttribute('file', $file)
            ->withAttribute('file_path', $file_path)
            ->withAttribute('base_dir', $base_dir);

        // Generate and store CSRF token
        $view->CSRF = $this->csrf->generate('files');

        if ($request->isPost()) {
            if (!$this->csrf->verify($request->getParam('xsrf'), 'files')) {
                return $response->withStatus(403)
                    ->withJson(['error' => ['code' => (int)403, 'msg' => 'XSRF Failure']]);
            }
        }

        // Set MAX_UPLOAD_SIZE based on PHP values
        $view->MAX_UPLOAD_SIZE = min(
            $this->_asBytes(ini_get('post_max_size')),
            $this->_asBytes(ini_get('upload_max_filesize'))
        );

        return $next($request, $response);
    }

    protected function _asBytes($ini_v)
    {
        $ini_v = trim($ini_v);
        $s = ['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10];

        return (int)$ini_v * ($s[strtolower(substr($ini_v, -1))] ?: 1);
    }
}