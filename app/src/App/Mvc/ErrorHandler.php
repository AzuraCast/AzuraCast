<?php
namespace App\Mvc;

use App\Acl;
use App\Flash;
use App\Session;
use App\Url;
use Entity;
use Exception;
use App\Http\Request;
use App\Http\Response;
use Monolog\Logger;

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

    /** @var Logger */
    protected $logger;

    /**
     * ErrorHandler constructor.
     * @param Url $url
     * @param Session $session
     * @param Flash $flash
     * @param View $view
     * @param Acl $acl
     */
    public function __construct(Url $url, Session $session, Flash $flash, View $view, Acl $acl, Logger $logger)
    {
        $this->url = $url;
        $this->session = $session;
        $this->flash = $flash;
        $this->view = $view;
        $this->acl = $acl;
        $this->logger = $logger;
    }

    public function __invoke(Request $req, Response $res, \Throwable $e)
    {
        // Don't log errors that are internal to the application.
        $e_level = ($e instanceof \App\Exception)
            ? $e->getLoggerLevel()
            : Logger::ERROR;

        $this->logger->addRecord($e_level, $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
        ]);

        /** @var Entity\User|null $user */
        $user = $req->getAttribute('user');
        $show_detailed = $this->acl->userAllowed($user, 'administer all') || !APP_IN_PRODUCTION;

        if ($req->isXhr() || APP_IS_COMMAND_LINE || APP_TESTING_MODE) {
            $api_response = new Entity\Api\Error(
                $e->getCode(),
                $e->getMessage(),
                ($show_detailed) ? $e->getTrace() : []
            );
            return $res->withStatus(500)->withJson($api_response);
        }

        if ($e instanceof \App\Exception\NotLoggedIn) {
            // Redirect to login page for not-logged-in users.
            $this->flash->addMessage(__('You must be logged in to access this page.'), 'red');

            // Set referrer for login redirection.
            $session = $this->session->get('login_referrer');
            $session->url = $this->url->current();

            return $res->withStatus(302)->withHeader('Location', $this->url->named('account:login'));
        }

        if ($e instanceof \App\Exception\PermissionDenied) {
            // Bounce back to homepage for permission-denied users.
            $this->flash->addMessage(__('You do not have permission to access this portion of the site.'),
                \App\Flash::ERROR);

            return $res
                ->withStatus(302)
                ->withHeader('Location', $this->url->named('home'));
        }

        if ($show_detailed) {
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