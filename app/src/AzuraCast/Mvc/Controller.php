<?php
namespace AzuraCast\Mvc;

use App\Config;
use App\Mvc\View;
use App\Url;
use AzuraCast\Acl\StationAcl;

use Doctrine\ORM\EntityManager;
use Slim\Container;

abstract class Controller extends \App\Mvc\Controller
{
    /** @var StationAcl */
    protected $acl;

    public function __construct(Container $di)
    {
        $this->di = $di;
        $this->config = $di[Config::class];
        $this->view = $di[View::class];
        $this->url = $di[Url::class];
        $this->em = $di[EntityManager::class];
        $this->acl = $di[StationAcl::class];
    }

    // TODO: Reimplement as middleware
    protected function preDispatch()
    {
        // Default to forbidding iframes
        $this->response = $this->response->withHeader('X-Frame-Options', 'DENY');
    }
}