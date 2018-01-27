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
     * Trigger rendering of template.
     *
     * @param Response $response
     * @param null $template_name
     * @param array $template_args
     * @return Response
     */
    public function render(Response $response, $template_name, $template_args = []): Response
    {
        $template = $this->view->render($template_name, $template_args);

        return $response
            ->withHeader('Content-type', 'text/html; charset=utf-8')
            ->write($template);
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
     * Render the page output as the supplied JSON.
     *
     * @param Response $response
     * @param $json_data
     * @return Response
     */
    protected function renderJson(Response $response, $json_data): Response
    {
        return $response
            ->withHeader('Content-type', 'application/json; charset=utf-8')
            ->write(json_encode($json_data));
    }

    /**
     * @param Response $response
     * @param $file_path
     * @param null $file_name
     * @return Response
     */
    protected function renderFile(Response $response, $file_path, $file_name = null): Response
    {
        set_time_limit(600);

        if ($file_name == null) {
            $file_name = basename($file_path);
        }

        $fh = fopen($file_path, 'rb');
        $stream = new \Slim\Http\Stream($fh);

        return $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', mime_content_type($file_path))
            ->withHeader('Content-Length', filesize($file_path))
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name)
            ->withBody($stream);
    }

    /**
     * @param Response $response
     * @param string $file_data The body of the file contents.
     * @param string $content_type The HTTP header content-type (i.e. text/csv)
     * @param null $file_name
     * @return Response
     */
    protected function renderStringAsFile(Response $response, $file_data, $content_type, $file_name = null): Response
    {
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Content-Type', $content_type);

        if ($file_name !== null) {
            $response = $response->withHeader('Content-Disposition', 'attachment; filename=' . $file_name);
        }

        return $response->write($file_data);
    }

    /* URL Redirection */

    /**
     * Redirect to the URL specified.
     *
     * @param Response $response
     * @param $new_url
     * @param int $code
     * @return Response
     */
    protected function redirect(Response $response, $new_url, $code = 302): Response
    {
        return $response
            ->withStatus($code)
            ->withHeader('Location', $new_url);
    }

    /**
     * Redirect to the homepage.
     *
     * @param Response $response
     * @param int $code
     * @return Response
     */
    protected function redirectHome(Response $response, $code = 302): Response
    {
        return $this->redirect($response, $this->url->named('home'), $code);
    }

    /**
     * Redirect with parameters to named route.
     *
     * @param Response $response
     * @param $name
     * @param array $route_params
     * @param int $code
     * @return Response
     */
    protected function redirectToName(Response $response, $name, $route_params = [], $code = 302): Response
    {
        return $this->redirect($response, $this->url->named($name, $route_params), $code);
    }

    /**
     * Store the current referring page in a session variable.
     *
     * @param string $namespace
     * @param bool $loose
     */
    protected function storeReferrer($namespace = 'default', $loose = true)
    {
        $session = $this->_getReferrerStorage($namespace);

        if (!isset($session->url) || ($loose && isset($session->url) && $this->url->current() != $this->url->referrer())) {
            $session->url = $this->url->referrer();
        }
    }

    /**
     * Retrieve the referring page stored in a session variable (if it exists).
     *
     * @param string $namespace
     * @return mixed
     */
    protected function getStoredReferrer($namespace = 'default')
    {
        $session = $this->_getReferrerStorage($namespace);
        return $session->url;
    }

    /**
     * Clear any session variable storing referrer data.
     *
     * @param string $namespace
     */
    protected function clearStoredReferrer($namespace = 'default')
    {
        $session = $this->_getReferrerStorage($namespace);
        unset($session->url);
    }

    /**
     * Redirect to a session-stored referrer URI, or a specified URI if none exists.
     *
     * @param Response $response
     * @param string $namespace
     * @param bool $default_url
     * @return Response
     */
    protected function redirectToStoredReferrer(Response $response, $namespace = 'default', $default_url = false): Response
    {
        $referrer = $this->getStoredReferrer($namespace);
        $this->clearStoredReferrer($namespace);

        $home_url = $this->url->named('home');
        if (strcmp($referrer, $this->request->getUri()->getPath()) == 0) {
            $referrer = $home_url;
        }

        if (trim($referrer) == '') {
            $referrer = ($default_url) ? $default_url : $home_url;
        }

        return $this->redirect($response, $referrer);
    }

    /**
     * @param string $namespace
     * @return \App\Session\NamespaceInterface
     */
    protected function _getReferrerStorage($namespace = 'default'): \App\Session\NamespaceInterface
    {
        /** @var \App\Session $session_manager */
        $session_manager = $this->di[\App\Session::class];
        return $session_manager->get('referrer_' . $namespace);
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