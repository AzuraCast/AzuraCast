<?php
namespace App\Controller\Admin;

use App\Acl;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Radio\Quota;
use App\Sync\Runner;
use Brick\Math\BigInteger;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    /** @var Acl */
    protected $acl;

    /** @var Logger */
    protected $logger;

    /** @var Runner */
    protected $sync;

    /**
     * @param Acl $acl
     * @param Logger $logger
     * @param Runner $sync
     * @see \App\Provider\AdminProvider
     */
    public function __construct(Acl $acl, Logger $logger, Runner $sync)
    {
        $this->acl = $acl;
        $this->logger = $logger;
        $this->sync = $sync;
    }

    /**
     * Main display.
     */
    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $view = $request->getView();
        $user = $request->getUser();

        // Remove the sidebar on the homepage.
        $view->sidebar = null;

        // Synchronization statuses
        if ($this->acl->userAllowed($user, Acl::GLOBAL_ALL)) {
            $view->sync_times = $this->sync->getSyncTimes();
        }

        $stations_base_dir = dirname(APP_INCLUDE_ROOT) . '/stations';

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

    public function syncAction(Request $request, Response $response, $type): ResponseInterface
    {
        $view = $request->getView();

        $handler = new TestHandler(Logger::DEBUG, false);
        $this->logger->pushHandler($handler);

        switch ($type) {
            case 'long':
                $this->sync->syncLong(true);
                break;

            case 'medium':
                $this->sync->syncMedium(true);
                break;

            case 'short':
                $this->sync->syncShort(true);
                break;

            case 'nowplaying':
            default:
                $this->sync->syncNowplaying(true);
                break;
        }

        $this->logger->popHandler();

        return $view->renderToResponse($response, 'system/log_view', [
            'sidebar'   => null,
            'title'     => __('Sync Task Output'),
            'log_records' => $handler->getRecords(),
        ]);
    }
}
