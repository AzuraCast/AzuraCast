<?php
namespace App\Http;

use App\Entity;
use App\Service\Sentry;
use Azura\Session;
use Azura\Settings;
use Azura\View;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

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

    /** @var Settings */
    protected $settings;

    /** @var int */
    protected $logger_level = Logger::ERROR;

    public function __construct(
        \Slim\App $app,
        Logger $logger,
        Session $session,
        Router $router,
        View $view,
        Sentry $sentry,
        Settings $settings
    ) {
        parent::__construct($app, $logger);

        $this->session = $session;
        $this->router = $router;
        $this->view = $view;
        $this->sentry = $sentry;
        $this->settings = $settings;
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        if ($exception instanceof \Azura\Exception) {
            $this->logger_level = $exception->getLoggerLevel();
        }

        $this->show_detailed = (!APP_IN_PRODUCTION && $this->logger_level >= Logger::ERROR);
        $this->return_json = $this->_returnJson($request);

        return parent::__invoke($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);
    }

    protected function _returnJson(ServerRequestInterface $req): bool
    {
        $isXhr = 'XMLHttpRequest' === $req->getHeaderLine('X-Requested-With');

        if ($isXhr || $this->settings->isCli() || $this->settings->isTesting()) {
            return true;
        }

        if ($req->hasHeader('Accept')) {
            $accept = $req->getHeader('Accept');

            if (in_array('application/json', $accept)) {
                return true;
            }
        }

        return false;
    }

    protected function writeToErrorLog(): void
    {
        $context = [
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'code' => $this->exception->getCode(),
        ];

        if ($this->exception instanceof \Azura\Exception) {
            $context['context'] = $this->exception->getLoggingContext();
            $context = array_merge($context, $this->exception->getExtraData());
        }

        if ($this->show_detailed) {
            $context['trace'] = array_slice($this->exception->getTrace(), 0, 5);
        }

        $this->logger->addRecord($this->logger_level, $this->exception->getMessage(), $context);
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

        if ($this->exception instanceof \App\Exception\NotLoggedIn) {
            $error_message = __('You must be logged in to access this page.');

            if ($this->return_json) {
                return ResponseHelper::withJson(
                    new Response(403),
                    new Entity\Api\Error(403, $error_message)
                );
            }

            // Redirect to login page for not-logged-in users.
            $this->session->flash(__('You must be logged in to access this page.'), 'red');

            // Set referrer for login redirection.
            $referrer_login = $this->session->get('login_referrer');
            $referrer_login->url = $this->request->getUri()->getPath();

            return ResponseHelper::withRedirect(
                new Response,
                $this->router->named('account:login')
            );
        }

        if ($this->exception instanceof \App\Exception\PermissionDenied) {
            $error_message = __('You do not have permission to access this portion of the site.');

            if ($this->return_json) {
                return ResponseHelper::withJson(
                    new Response(403),
                    new Entity\Api\Error(403, $error_message)
                );
            }

            // Bounce back to homepage for permission-denied users.
            $this->session->flash(__('You do not have permission to access this portion of the site.'),
                Session\Flash::ERROR);

            return ResponseHelper::withRedirect(
                new Response,
                $this->router->named('home')
            );
        }

        $this->sentry->handleException($this->exception);

        if ($this->return_json) {
            $api_response = new Entity\Api\Error(
                $this->exception->getCode(),
                $this->exception->getMessage(),
                ($this->exception instanceof \Azura\Exception) ? $this->exception->getFormattedMessage() : $this->exception->getMessage()
            );

            return ResponseHelper::withJson(new Response(500), $api_response);
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

            $res = new Response(500);
            $res->getBody()->write($run->handleException($this->exception));
            return $res;
        }

        return $this->view->renderToResponse(
            new Response(500),
            'system/error_general',
            [
                'exception' => $this->exception,
            ]
        );
    }
}
