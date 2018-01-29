<?php
namespace App\Mvc;

use App\Acl;
use App\Config;
use App\Url;
use Doctrine\ORM\EntityManager;
use Slim\Container;
use Slim\Http\Response;

abstract class Controller
{
    /** @var Container */
    protected $di;

    /** @var Config */
    protected $config;

    /** @var View */
    protected $view;

    /** @var Url */
    protected $url;

    /** @var EntityManager */
    protected $em;

    /** @var Acl */
    protected $acl;

    public function __construct(Container $di)
    {
        $this->di = $di;
        $this->config = $di[Config::class];
        $this->view = $di[View::class];
        $this->url = $di[Url::class];
        $this->em = $di[EntityManager::class];
        $this->acl = $di[Acl::class];
    }



    /**
     * Render a form using the system's form template.
     *
     * @param Response $response
     * @param \App\Form $form
     * @param string $mode
     * @param null $form_title
     * @return Response
     */
    protected function renderForm(Response $response, \App\Form $form, $mode = 'edit', $form_title = null): Response
    {
        if ($form_title) {
            $this->view->title = $form_title;
        }

        $this->view->form = $form;
        $this->view->render_mode = $mode;

        return $this->render($response, 'system/form_page');
    }

    /**
     * Store a message for display as a "flash" style success/error/warning message.
     *
     * @param $message
     * @param string $level
     */
    protected function alert($message, $level = \App\Flash::INFO)
    {
        /** @var \App\Flash $flash */
        $flash = $this->di[\App\Flash::class];
        $flash->addMessage($message, $level, true);
    }
}