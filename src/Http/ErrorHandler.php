<?php
namespace App\Http;

use App\Entity;
use App\Service\Sentry;
use Azura\Session;
use Azura\Settings;
use Azura\View;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;

class ErrorHandler extends \Azura\Http\ErrorHandler
{
    /** @var Session */
    protected $session;

    /** @var Router */
    protected $router;

    /** @var View */
    protected $view;

    /** @var Sentry */
    protected $sentry;

    public function __construct(
        \Slim\App $app,
        Logger $logger,
        Session $session,
        Router $router,
        View $view,
        Sentry $sentry,
        Settings $settings
    ) {
        parent::__construct($app, $logger, $settings);

        $this->session = $session;
        $this->router = $router;
        $this->view = $view;
        $this->sentry = $sentry;
    }

    protected function respond(): ResponseInterface
    {
        if (!function_exists('__')) {
            $translator = new \Gettext\Translator();
            $translator->register();
        }

        // Special handling for cURL requests.
        $ua = $this->request->getHeaderLine('User-Agent');

        if (false !== stripos($ua, 'curl')) {
            $response = $this->responseFactory->createResponse($this->statusCode);

            $response->getBody()
                ->write('Error: '.$this->exception->getMessage().' on '.$this->exception->getFile().' L'.$this->exception->getLine());

            return $response;
        }

        $this->view->addData([
            'request' => $this->request,
        ]);

        if ($this->exception instanceof HttpNotFoundException) {
            return $this->view->renderToResponse(
                $this->responseFactory->createResponse(404),
                'system/error_pagenotfound'
            );
        }

        if ($this->exception instanceof \App\Exception\NotLoggedIn) {
            /** @var Response $response */
            $response = $this->responseFactory->createResponse(403);

            $error_message = __('You must be logged in to access this page.');

            if ($this->return_json) {
                return $response->withJson(new Entity\Api\Error(403, $error_message));
            }

            // Redirect to login page for not-logged-in users.
            $this->session->flash(__('You must be logged in to access this page.'), 'red');

            // Set referrer for login redirection.
            $referrer_login = $this->session->get('login_referrer');
            $referrer_login->url = $this->request->getUri()->getPath();

            return $response->withRedirect((string)$this->router->named('account:login'));
        }

        if ($this->exception instanceof \App\Exception\PermissionDenied) {
            /** @var Response $response */
            $response = $this->responseFactory->createResponse(403);

            $error_message = __('You do not have permission to access this portion of the site.');

            if ($this->return_json) {
                return $response->withJson(new Entity\Api\Error(403, $error_message));
            }

            // Bounce back to homepage for permission-denied users.
            $this->session->flash(__('You do not have permission to access this portion of the site.'),
                Session\Flash::ERROR);

            return $response->withRedirect((string)$this->router->named('home'));
        }

        $this->sentry->handleException($this->exception);

        /** @var Response $response */
        $response = $this->responseFactory->createResponse(500);

        if ($this->return_json) {
            $api_response = new Entity\Api\Error(
                $this->exception->getCode(),
                $this->exception->getMessage(),
                ($this->exception instanceof \Azura\Exception) ? $this->exception->getFormattedMessage() : $this->exception->getMessage()
            );

            return $response->withJson($api_response);
        }

        if ($this->show_detailed && class_exists('\Whoops\Run')) {
            // Register error-handler.
            $handler = new \Whoops\Handler\PrettyPageHandler;
            $handler->setPageTitle('An error occurred!');

            if ($this->exception instanceof \Azura\Exception) {
                $extra_tables = $this->exception->getExtraData();
                foreach($extra_tables as $legend => $data) {
                    $handler->addDataTable($legend, $data);
                }
            }

            $run = new \Whoops\Run;
            $run->prependHandler($handler);

            return $response->write($run->handleException($this->exception));
        }

        return $this->view->renderToResponse(
            $response,
            'system/error_general',
            [
                'exception' => $this->exception,
            ]
        );
    }
}
