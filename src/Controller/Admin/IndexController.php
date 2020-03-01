<?php
namespace App\Controller\Admin;

use App\Acl;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Quota;
use App\Settings;
use App\Sync\Runner;
use Brick\Math\BigInteger;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Runner $sync
    ): ResponseInterface {
        $view = $request->getView();
        $user = $request->getUser();

        // Remove the sidebar on the homepage.
        $view->addData(['sidebar' => null]);

        // Synchronization statuses
        $acl = $request->getAcl();

        if ($acl->userAllowed($user, Acl::GLOBAL_ALL)) {
            $view->addData([
                'sync_times' => $sync->getSyncTimes(),
            ]);
        }

        $stations_base_dir = Settings::getInstance()->getStationDirectory();

        $space_total = BigInteger::of(disk_total_space($stations_base_dir));
        $space_free = BigInteger::of(disk_free_space($stations_base_dir));
        $space_used = $space_total->minus($space_free);

        return $view->renderToResponse($response, 'admin/index/index', [
            'load' => sys_getloadavg(),
            'space_percent' => Quota::getPercentage($space_used, $space_total),
            'space_used' => Quota::getReadableSize($space_used),
            'space_total' => Quota::getReadableSize($space_total),
        ]);
    }
}
