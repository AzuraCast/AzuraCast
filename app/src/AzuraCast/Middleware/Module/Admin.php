<?php
namespace AzuraCast\Middleware\Module;

use App\Mvc\View;
use App\Session;
use AzuraCast\Acl\StationAcl;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Module middleware for the /admin pages.
 */
class Admin
{
    /** @var StationAcl */
    protected $acl;

    /** @var array */
    protected $dashboard_config;

    public function __construct(StationAcl $acl, $dashboard_config)
    {
        $this->acl = $acl;
        $this->dashboard_config = $dashboard_config;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        // Load dashboard.
        $panels = $this->dashboard_config;

        foreach ($panels as $sidebar_category => &$sidebar_info) {
            foreach ($sidebar_info['items'] as $item_name => $item_params) {
                $permission = $item_params['permission'];
                if (!is_bool($permission)) {
                    $permission = $this->acl->isAllowed($permission);
                }

                if (!$permission) {
                    unset($sidebar_info['items'][$item_name]);
                }
            }

            if (empty($sidebar_info['items'])) {
                unset($panels[$sidebar_category]);
            }
        }

        unset($sidebar_info);

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        $view->admin_panels = $panels;
        $view->sidebar = $view->render('admin/sidebar');

        return $next($request, $response);
    }
}