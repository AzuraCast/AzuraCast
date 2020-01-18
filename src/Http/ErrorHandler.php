<?php
namespace App\Http;

use App\Entity;
use App\Exception\NotLoggedInException;
use App\Exception\PermissionDeniedException;
use App\Service\Sentry;
use App\Settings;
use Azura\Exception;
use Azura\Session\Flash;
use Azura\View;
use Gettext\Translator;
use Mezzio\Session\SessionInterface;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use Slim\App;
use Slim\Exception\HttpException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class ErrorHandler extends \Azura\Http\ErrorHandler
{
    protected Router $router;

    protected View $view;

    protected Sentry $sentry;

    public function __construct(
        App $app,
        Logger $logger,
        Router $router,
        View $view,
        Sentry $sentry,
        Settings $settings
    ) {
        parent::__construct($app, $logger, $settings);

        $this->router = $router;
        $this->view = $view;
        $this->sentry = $sentry;
    }

    protected function respond(): ResponseInterface
    {
        if (!function_exists('__')) {
            $translator = new Translator();
            $translator->register();
        }

        // Special handling for cURL requests.
        $ua = $this->request->getHeaderLine('User-Agent');

        if (false !== stripos($ua, 'curl')) {
            $response = $this->responseFactory->createResponse($this->statusCode);

            $response->getBody()
                ->write('Error: ' . $this->exception->getMessage() . ' on ' . $this->exception->getFile() . ' L' . $this->exception->getLine());

            return $response;
        }

        $this->view->addData([
            'request' => $this->request,
        ]);

        if ($this->exception instanceof HttpException) {
            /** @var Response $response */
            $response = $this->responseFactory->createResponse($this->exception->getCode());

            if ($this->returnJson) {
                return $response->withJson(new Entity\Api\Error(
                    $this->exception->getCode(),
                    $this->exception->getMessage()
                ));
            }

            return $this->view->renderToResponse(
                $response,
                'system/error_http',
                [
                    'exception' => $this->exception,
                ]
            );
        }

        if ($this->exception instanceof NotLoggedInException) {
            /** @var SessionInterface $session */
            $session = $this->request->getAttribute(ServerRequest::ATTR_SESSION);

            /** @var Flash $flash */
            $flash = $this->request->getAttribute(ServerRequest::ATTR_SESSION_FLASH);

            /** @var Response $response */
            $response = $this->responseFactory->createResponse(403);

            $error_message = __('You must be logged in to access this page.');

            if ($this->returnJson) {
                return $response->withJson(new Entity\Api\Error(403, $error_message));
            }

            // Redirect to login page for not-logged-in users.
            $flash->addMessage(__('You must be logged in to access this page.'), Flash::ERROR);

            // Set referrer for login redirection.
            $session->set('login_referrer', $this->request->getUri()->getPath());

            return $response->withRedirect((string)$this->router->named('account:login'));
        }

        if ($this->exception instanceof PermissionDeniedException) {
            /** @var Flash $flash */
            $flash = $this->request->getAttribute(ServerRequest::ATTR_SESSION_FLASH);

            /** @var Response $response */
            $response = $this->responseFactory->createResponse(403);

            $error_message = __('You do not have permission to access this portion of the site.');

            if ($this->returnJson) {
                return $response->withJson(new Entity\Api\Error(403, $error_message));
            }

            // Bounce back to homepage for permission-denied users.
            $flash->addMessage(__('You do not have permission to access this portion of the site.'),
                Flash::ERROR);

            return $response->withRedirect((string)$this->router->named('home'));
        }

        if (!in_array($this->loggerLevel, [LogLevel::INFO, LogLevel::DEBUG, LogLevel::NOTICE], true)) {
            $this->sentry->handleException($this->exception);
        }

        /** @var Response $response */
        $response = $this->responseFactory->createResponse(500);

        if ($this->returnJson) {
            if ($this->showDetailed) {
                return $response->withJson([
                    'code' => $this->exception->getCode(),
                    'message' => $this->exception->getMessage(),
                    'file' => $this->exception->getFile(),
                    'line' => $this->exception->getLine(),
                    'trace' => $this->exception->getTrace(),
                ]);
            }

            $api_response = new Entity\Api\Error(
                $this->exception->getCode(),
                $this->exception->getMessage(),
                ($this->exception instanceof Exception) ? $this->exception->getFormattedMessage() : $this->exception->getMessage()
            );

            return $response->withJson($api_response);
        }

        if ($this->showDetailed && class_exists('\Whoops\Run')) {
            // Register error-handler.
            $handler = new PrettyPageHandler;
            $handler->setPageTitle('An error occurred!');

            if ($this->exception instanceof Exception) {
                $extra_tables = $this->exception->getExtraData();
                foreach ($extra_tables as $legend => $data) {
                    $handler->addDataTable($legend, $data);
                }
            }

            $run = new Run;
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
