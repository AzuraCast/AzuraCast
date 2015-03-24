<?php
namespace DF\Phalcon;

class ErrorHandler
{
    public static function handle(\Exception $e, \Phalcon\DiInterface $di)
    {
        if ($e instanceof \DF\Exception\NotLoggedIn)
        {
            // Redirect to login page for not-logged-in users.
            \DF\Flash::addMessage('You must be logged in to access this page!');

            $login_url = $di->get('url')->get('account/login');

            $response = $di->get('response');
            $response->redirect($login_url, 302);
            $response->send();
            return;
        }
        elseif ($e instanceof \DF\Exception\PermissionDenied)
        {
            // Bounce back to homepage for permission-denied users.
            \DF\Flash::addMessage('You do not have permission to access this portion of the site.', \DF\Flash::ERROR);

            $home_url = $di->get('url')->get('');

            $response = $di->get('response');
            $response->redirect($home_url, 302);
            $response->send();
            return;
        }
        elseif ($e instanceof \Phalcon\Mvc\Dispatcher\Exception)
        {
            // Handle 404 page not found exception
            if ($di->has('view')) {
                $view = $di->get('view');
                $view->disable();
            }

            $view = \DF\Phalcon\View::getView(array());
            $result = $view->getRender('error', 'pagenotfound');

            $response = $di->get('response');
            $response->setStatusCode(404, "Not Found");

            $response->setContent($result);
            $response->send();
            return;
        }
        elseif ($e instanceof \DF\Exception\Bootstrap)
        {
            // Bootstrapping error; cannot render template for error display.
            if (DF_APPLICATION_ENV != 'production')
            {
                self::renderPretty($e, $di);
                return;
            }
            else
            {
                $response = $di->get('response');
                $response->setStatusCode(500, "Internal Server Error");

                $exception_msg = "<b>Application core exception: </b>\n<blockquote>"
                    . $e->getMessage()
                    . "</blockquote>"
                    . "\n" . "on line <b>"
                    . $e->getLine()
                    . "</b> of <i>"
                    . $e->getFile()
                    . "</i>";

                $response->setContent($exception_msg);
                $response->send();
                return;
            }
        }
        else
        {
            if ($di->has('view')) {
                $view = $di->get('view');
                $view->disable();
            }

            $show_debug = false;
            if ($di->has('acl'))
            {
                $acl = $di->get('acl');
                if ($acl->isAllowed('administer all'))
                    $show_debug = true;
            }

            if (DF_APPLICATION_ENV != 'production')
                $show_debug = true;

            if ($show_debug)
            {
                self::renderPretty($e, $di);
                return;
            }
            else
            {
                $view = \DF\Phalcon\View::getView(array());

                $view->setVar('exception', $e);

                $result = $view->getRender('error', 'general');

                $response = $di->get('response');
                $response->setStatusCode(500, "Internal Server Error");
                $response->setContent($result);
                $response->send();
                return;
            }
        }
    }

    public static function renderPretty(\Exception $e, \Phalcon\DIInterface $di)
    {
        $response = $di->get('response');
        $response->setStatusCode(500, "Internal Server Error");

        // Register error-handler.
        $run = new \Whoops\Run;

        $handler = new \Whoops\Handler\PrettyPageHandler;
        $handler->setPageTitle('An error occurred!');
        $run->pushHandler($handler);

        $run->handleException($e);

        $response->send();
        return;
    }
}