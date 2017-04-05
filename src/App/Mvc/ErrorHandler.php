<?php
namespace App\Mvc;

use Exception;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ErrorHandler
{
    public static function handle(ContainerInterface $di, Request $req, Response $res, Exception $e)
    {
        if ($e instanceof \App\Exception\NotLoggedIn) {
            // Redirect to login page for not-logged-in users.
            $flash = $di['flash'];
            $flash->addMessage('<b>Error:</b> You must be logged in to access this page!', 'red');

            // Set referrer for login redirection.
            $session = $di['session'];
            $session = $session->get('referrer_login');

            $session->url = $di['url']->current();

            // Redirect to login page.
            $login_url = $di['url']->named('account:login');

            return $res->withStatus(302)->withHeader('Location', $login_url);
        } elseif ($e instanceof \App\Exception\PermissionDenied) {
            // Bounce back to homepage for permission-denied users.
            $di['flash']->addMessage('You do not have permission to access this portion of the site.',
                \App\Flash::ERROR);

            $home_url = $di['url']->named('home');

            return $res->withStatus(302)->withHeader('Location', $home_url);
        } elseif (APP_IS_COMMAND_LINE) {
            $body = $res->getBody();
            $body->write(json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]));

            return $res->withStatus(500)
                ->withBody($body);
        } else {
            $show_debug = false;
            if ($di->has('acl')) {
                $acl = $di->get('acl');
                if ($acl->isAllowed('administer all')) {
                    $show_debug = true;
                }
            }

            if (APP_APPLICATION_ENV != 'production') {
                $show_debug = true;
            }

            if ($show_debug) {
                $view = $di->get('view');
                $view->disable();

                // Register error-handler.
                $handler = new \Whoops\Handler\PrettyPageHandler;
                $handler->setPageTitle('An error occurred!');

                $run = new \Whoops\Run;
                $run->pushHandler($handler);

                $body = $res->getBody();
                $body->write($run->handleException($e));

                return $res->withStatus(500)
                    ->withBody($body);
            } else {
                $view = $di->get('view');
                $view->exception = $e;

                $template = $view->render('system/error_general');

                $body = $res->getBody();
                $body->write($template);

                return $res->withStatus(500)
                    ->withBody($body);
            }
        }
    }
}