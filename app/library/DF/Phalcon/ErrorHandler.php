<?php
namespace DF\Phalcon;

class ErrorHandler
{
    public static function handle(\Exception $e, \Phalcon\DiInterface $di)
    {
        // Special handling for common DF errors.
        if ($e instanceof \DF\Exception\NotLoggedIn)
        {
            \DF\Flash::addMessage('You must be logged in to access this page!');

            $login_url = $di->get('url')->get('account/login');

            $response = $di->get('response');
            $response->redirect($login_url, 302);
            $response->send();
            return;
        }
        elseif ($e instanceof \DF\Exception\PermissionDenied)
        {
            \DF\Flash::addMessage('You do not have permission to access this portion of the site.', \DF\Flash::ERROR);

            $home_url = $di->get('url')->get('');

            $response = $di->get('response');
            $response->redirect($home_url, 302);
            $response->send();
            return;
        }
        elseif ($e instanceof \Phalcon\Mvc\Dispatcher\Exception)
        {
            $err_url = $di->get('url')->get('error/pagenotfound');

            $response = $di->get('response');
            $response->redirect($err_url, 302);
            $response->send();
            return;
        }

        // Register error-handler.
        $run = new \Whoops\Run;

        $handler = new \Whoops\Handler\PrettyPageHandler;
        $handler->setPageTitle('An error occurred!');
        $run->pushHandler($handler);

        $run->handleException($e);
    }
}