<?php
namespace App\Mvc;

use Exception;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ErrorHandler
{
    public static function handle(ContainerInterface $di, Request $req, Response $res, \Throwable $e)
    {
        if ($e instanceof \App\Exception\NotLoggedIn) {
            // Redirect to login page for not-logged-in users.

            /** @var \App\Flash $flash */
            $flash = $di[\App\Flash::class];
            $flash->addMessage('<b>Error:</b> You must be logged in to access this page!', 'red');

            // Set referrer for login redirection.

            /** @var \App\Session $session */
            $session = $di[\App\Session::class];
            $session = $session->get('referrer_login');

            /** @var \App\Url $url */
            $url = $di[\App\Url::class];
            $session->url = $url->current();

            return $res->withStatus(302)->withHeader('Location', $url->named('account:login'));
        } elseif ($e instanceof \App\Exception\PermissionDenied) {
            // Bounce back to homepage for permission-denied users.
            /** @var \App\Flash $flash */
            $flash = $di[\App\Flash::class];

            $flash->addMessage('You do not have permission to access this portion of the site.',
                \App\Flash::ERROR);

            /** @var \App\Url $url */
            $url = $di[\App\Url::class];

            return $res->withStatus(302)->withHeader('Location', $url->named('home'));
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
            /** @var \App\Mvc\View $view */
            $view = $di[\App\Mvc\View::class];

            if (self::showDetailedDebugInfo($di)) {
                $view->disable();

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

                $body = $res->getBody();
                $body->write($run->handleException($e));

                return $res->withStatus(500)
                    ->withBody($body);
            } else {
                $view->exception = $e;

                $template = $view->render('system/error_general');

                $body = $res->getBody();
                $body->write($template);

                return $res->withStatus(500)
                    ->withBody($body);
            }
        }
    }

    protected static function showDetailedDebugInfo(ContainerInterface $di): bool
    {
        if ($di->has(\App\Acl::class)) {
            /** @var \App\Acl $acl */
            $acl = $di[\App\Acl::class];

            if ($acl->isAllowed('administer all')) {
                return true;
            }
        }

        return !APP_IN_PRODUCTION;
    }
}