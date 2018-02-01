<?php
namespace App\Mvc;

use App\Acl;
use App\Flash;
use App\Session;
use App\Url;
use Exception;
use App\Http\Request;
use App\Http\Response;

class ErrorHandler
{
    /** @var Url */
    protected $url;

    /** @var Session */
    protected $session;

    /** @var Flash */
    protected $flash;

    /** @var View */
    protected $view;

    /** @var Acl */
    protected $acl;

    /**
     * ErrorHandler constructor.
     * @param Url $url
     * @param Session $session
     * @param Flash $flash
     * @param View $view
     * @param Acl $acl
     */
    public function __construct(Url $url, Session $session, Flash $flash, View $view, Acl $acl)
    {
        $this->url = $url;
        $this->session = $session;
        $this->flash = $flash;
        $this->view = $view;
        $this->acl = $acl;
    }

    public function __invoke(Request $req, Response $res, \Throwable $e)
    {
        if ($e instanceof \App\Exception\NotLoggedIn) {
            // Redirect to login page for not-logged-in users.
            $this->flash->addMessage('<b>Error:</b> You must be logged in to access this page!', 'red');

            // Set referrer for login redirection.
            $session = $this->session->get('referrer_login');
            $session->url = $this->url->current();

            return $res->withStatus(302)->withHeader('Location', $this->url->named('account:login'));
        }

        if ($e instanceof \App\Exception\PermissionDenied) {
            // Bounce back to homepage for permission-denied users.
            $this->flash->addMessage('You do not have permission to access this portion of the site.',
                \App\Flash::ERROR);

            return $res
                ->withStatus(302)
                ->withHeader('Location', $this->url->named('home'));
        }

        if (APP_IS_COMMAND_LINE) {
            return $res->withStatus(500)->withJson([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }

        if ($this->acl->isAllowed('administer all') || !APP_IN_PRODUCTION) {
            // Register error-handler.
            $handler = new \Whoops\Handler\PrettyPageHandler;
            $handler->setPageTitle('An error occurred!');

            if ($e instanceof \App\Exception) {
                $extra_tables = $e->getExtraData();
                foreach($extra_tables as $legend => $data) {
                    $handler->addDataTable($legend, $data);
                }
            }

            $run = new \Whoops\Run;
            $run->pushHandler($handler);

            return $res->withStatus(500)->write($run->handleException($e));
        }

        return $this->view->renderToResponse($res->withStatus(500), 'system/error_general', [
            'exception' => $e,
        ]);
    }
}