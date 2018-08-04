<?php
namespace App\Middleware\Module;

use App\Entity;
use App\Acl;
use App\Http\Request;
use App\Http\Response;

/**
 * Module middleware for the /admin pages.
 */
class Admin
{
    /** @var Acl */
    protected $acl;

    /** @var array */
    protected $dashboard_config;

    public function __construct(Acl $acl, $dashboard_config)
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
        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        // Load dashboard.
        $panels = $this->dashboard_config;

        foreach ($panels as $sidebar_category => &$sidebar_info) {
            foreach ($sidebar_info['items'] as $item_name => $item_params) {
                $permission = $item_params['permission'];
                if (!is_bool($permission)) {
                    $permission = $this->acl->userAllowed($user, $permission);
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

        $view = $request->getView();

        $view->admin_panels = $panels;
        $view->sidebar = $view->render('admin/sidebar');

        return $next($request, $response);
    }
}
