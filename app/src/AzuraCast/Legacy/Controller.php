<?php
namespace AzuraCast\Legacy;

use App\Config;
use App\Mvc\View;
use App\Url;
use AzuraCast\Acl\StationAcl;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;

/**
 * @deprecated
 */
abstract class Controller
{
    /** @var ContainerInterface */
    protected $di;

    /** @var Config */
    protected $config;

    /** @var View */
    protected $view;

    /** @var Url */
    protected $url;

    /** @var EntityManager */
    protected $em;

    /** @var StationAcl */
    protected $acl;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        $this->config = $di[Config::class];
        $this->view = $di[View::class];
        $this->url = $di[Url::class];
        $this->em = $di[EntityManager::class];
        $this->acl = $di[StationAcl::class];
    }
}