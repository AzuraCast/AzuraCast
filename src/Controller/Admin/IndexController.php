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

        $stationsBaseDir = Settings::getInstance()->getStationDirectory();

        $spaceTotal = BigInteger::of(disk_total_space($stationsBaseDir));
        $spaceFree = BigInteger::of(disk_free_space($stationsBaseDir));
        $spaceUsed = $spaceTotal->minus($spaceFree);

        // Get memory info.
        $meminfoRaw = explode("\n", file_get_contents("/proc/meminfo"));
        $meminfo = [];
        foreach ($meminfoRaw as $line) {
            [$key, $val] = explode(":", $line);
            $meminfo[$key] = trim($val);
        }

        $memoryTotal = Quota::convertFromReadableSize($meminfo['MemTotal']) ?? BigInteger::zero();
        $memoryFree = Quota::convertFromReadableSize($meminfo['MemAvailable']) ?? BigInteger::zero();
        $memoryUsed = $memoryTotal->minus($memoryFree);

        return $view->renderToResponse($response, 'admin/index/index', [
            'load' => sys_getloadavg(),
            'space_percent' => Quota::getPercentage($spaceUsed, $spaceTotal),
            'space_used' => Quota::getReadableSize($spaceUsed),
            'space_total' => Quota::getReadableSize($spaceTotal),
            'memory_percent' => Quota::getPercentage($memoryUsed, $memoryTotal),
            'memory_used' => Quota::getReadableSize($memoryUsed),
            'memory_total' => Quota::getReadableSize($memoryTotal),
        ]);
    }
}
