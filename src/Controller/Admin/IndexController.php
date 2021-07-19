<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Acl;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Quota;
use App\Sync\Runner;
use Brick\Math\BigInteger;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Runner $sync,
        Environment $environment
    ): ResponseInterface {
        $view = $request->getView();

        // Remove the sidebar on the homepage.
        $view->addData(['sidebar' => null]);

        // Synchronization statuses
        $acl = $request->getAcl();

        if ($acl->isAllowed(Acl::GLOBAL_ALL)) {
            $view->addData(
                [
                    'sync_times' => $sync->getSyncTimes(),
                ]
            );
        }

        $stationsBaseDir = $environment->getStationDirectory();

        $spaceTotalFloat = disk_total_space($stationsBaseDir);
        $spaceTotal = (is_float($spaceTotalFloat))
            ? BigInteger::of($spaceTotalFloat)
            : BigInteger::zero();

        $spaceFreeFloat = disk_free_space($stationsBaseDir);
        $spaceFree = (is_float($spaceFreeFloat))
            ? BigInteger::of($spaceFreeFloat)
            : BigInteger::zero();

        $spaceUsed = $spaceTotal->minus($spaceFree);

        // Get memory info.
        $meminfoRaw = file("/proc/meminfo", FILE_IGNORE_NEW_LINES) ?: [];
        $meminfo = [];
        foreach ($meminfoRaw as $line) {
            if (str_contains($line, ':')) {
                [$key, $val] = explode(":", $line);
                $meminfo[$key] = trim($val);
            }
        }

        $memoryTotal = Quota::convertFromReadableSize($meminfo['MemTotal']) ?? BigInteger::zero();
        $memoryFree = Quota::convertFromReadableSize($meminfo['MemAvailable']) ?? BigInteger::zero();
        $memoryUsed = $memoryTotal->minus($memoryFree);

        return $view->renderToResponse(
            $response,
            'admin/index/index',
            [
                'load' => sys_getloadavg(),
                'space_percent' => Quota::getPercentage($spaceUsed, $spaceTotal),
                'space_used' => Quota::getReadableSize($spaceUsed),
                'space_total' => Quota::getReadableSize($spaceTotal),
                'memory_percent' => Quota::getPercentage($memoryUsed, $memoryTotal),
                'memory_used' => Quota::getReadableSize($memoryUsed),
                'memory_total' => Quota::getReadableSize($memoryTotal),
            ]
        );
    }
}
