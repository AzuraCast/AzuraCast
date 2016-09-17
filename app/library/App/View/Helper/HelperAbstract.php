<?php
namespace App\View\Helper;

use Interop\Container\ContainerInterface;

class HelperAbstract
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    protected $view;
    protected $viewHelper;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        $this->view = $di->get('view');
        $this->viewHelper = $di->get('view_helper');
    }
}