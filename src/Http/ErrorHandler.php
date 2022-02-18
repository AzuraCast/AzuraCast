<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity;
use App\Environment;
use App\Exception;
use App\Exception\NotLoggedInException;
use App\Exception\PermissionDeniedException;
use App\Middleware\InjectSession;
use App\Session\Flash;
use App\View;
use DI\FactoryInterface;
use Gettext\Translator;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Slim\App;
use Slim\Exception\HttpException;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class ErrorHandler extends \Slim\Handlers\ErrorHandler
{
    protected bool $returnJson = false;

    protected bool $showDetailed = false;

    protected string $loggerLevel = LogLevel::ERROR;

    public function __construct(
        protected FactoryInterface $factory,
        protected Router $router,
        protected InjectSession $injectSession,
        protected Environment $environment,
        App $app,
        Logger $logger,
    ) {
        parent::__construct($app->getCallableResolver(), $app->getResponseFactory(), $logger);
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        if ($exception instanceof Exception\WrappedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }

        if ($exception instanceof Exception) {
            $this->loggerLevel = $exception->getLoggerLevel();
        } elseif ($exception instanceof HttpException) {
            $this->loggerLevel = LogLevel::INFO;
        }

        $this->showDetailed = (!$this->environment->isProduction() && !in_array(
            $this->loggerLevel,
            [LogLevel::DEBUG, LogLevel::INFO, LogLevel::NOTICE],
            true
        ));
        $this->returnJson = $this->shouldReturnJson($request);

        return parent::__invoke($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);
    }

    protected function shouldReturnJson(ServerRequestInterface $req): bool
    {
        $xhr = $req->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if ($xhr || $this->environment->isCli() || $this->environment->isTesting()) {
            return true;
        }

        if ($req->hasHeader('Accept')) {
            $accept = $req->getHeaderLine('Accept');
            if (false !== stripos($accept, 'application/json')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function writeToErrorLog(): void
    {
        $context = [
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'code' => $this->exception->getCode(),
        ];

        if ($this->exception instanceof Exception) {
            $context['context'] = $this->exception->getLoggingContext();
            $context = array_merge($context, $this->exception->getExtraData());
        }

        if ($this->showDetailed) {
            $context['trace'] = array_slice($this->exception->getTrace(), 0, 5);
        }

        $this->logger->log($this->loggerLevel, $this->exception->getMessage(), $context);
    }

    protected function respond(): ResponseInterface
    {
        if (!function_exists('__')) {
            $translator = new Translator();
            $translator->register();
        }

        // Special handling for cURL requests.
        $ua = $this->request->getHeaderLine('User-Agent');

        if (false !== stripos($ua, 'curl') || false !== stripos($ua, 'Liquidsoap')) {
            $response = $this->responseFactory->createResponse($this->statusCode);

            $response->getBody()->write(
                sprintf(
                    'Error: %s on %s L%s',
                    $this->exception->getMessage(),
                    $this->exception->getFile(),
                    $this->exception->getLine()
                )
            );

            return $response;
        }

        if ($this->exception instanceof HttpException) {
            /** @var Response $response */
            $response = $this->responseFactory->createResponse($this->exception->getCode());

            if ($this->returnJson) {
                $apiResponse = Entity\Api\Error::fromException($this->exception, $this->showDetailed);
                return $response->withJson($apiResponse);
            }

            try {
                $view = $this->factory->make(
                    View::class,
                    [
                        'request' => $this->request,
                    ]
                );

                return $view->renderToResponse(
                    $response,
                    'system/error_http',
                    [
                        'exception' => $this->exception,
                    ]
                );
            } catch (Throwable) {
                return parent::respond();
            }
        }

        if ($this->exception instanceof NotLoggedInException) {
            /** @var Response $response */
            $response = $this->responseFactory->createResponse(403);

            if ($this->returnJson) {
                $error = Entity\Api\Error::fromException($this->exception);
                $error->code = 403;
                $error->message = __('You must be logged in to access this page.');

                return $response->withJson($error);
            }

            // Redirect to login page for not-logged-in users.
            $sessionPersistence = $this->injectSession->getSessionPersistence($this->request);
            $session = $sessionPersistence->initializeSessionFromRequest($this->request);

            $flash = new Flash($session);
            $flash->addMessage(__('You must be logged in to access this page.'), Flash::ERROR);

            // Set referrer for login redirection.
            $session->set('login_referrer', $this->request->getUri()->getPath());

            $response = $sessionPersistence->persistSession($session, $response);

            /** @var Response $response */
            return $response->withRedirect((string)$this->router->named('account:login'));
        }

        if ($this->exception instanceof PermissionDeniedException) {
            /** @var Response $response */
            $response = $this->responseFactory->createResponse(403);

            if ($this->returnJson) {
                $error = Entity\Api\Error::fromException($this->exception);
                $error->code = 403;
                $error->message = __('You do not have permission to access this portion of the site.');

                return $response->withJson($error);
            }

            $sessionPersistence = $this->injectSession->getSessionPersistence($this->request);
            $session = $sessionPersistence->initializeSessionFromRequest($this->request);

            $flash = new Flash($session);
            $flash->addMessage(
                __('You do not have permission to access this portion of the site.'),
                Flash::ERROR
            );

            $response = $sessionPersistence->persistSession($session, $response);

            // Bounce back to homepage for permission-denied users.
            /** @var Response $response */
            return $response->withRedirect((string)$this->router->named('home'));
        }

        /** @var Response $response */
        $response = $this->responseFactory->createResponse(500);

        if ($this->returnJson) {
            $apiResponse = Entity\Api\Error::fromException($this->exception, $this->showDetailed);
            return $response->withJson($apiResponse);
        }

        if ($this->showDetailed && class_exists(Run::class)) {
            // Register error-handler.
            $handler = new PrettyPageHandler();
            $handler->setPageTitle('An error occurred!');

            if ($this->exception instanceof Exception) {
                foreach ($this->exception->getExtraData() as $legend => $data) {
                    $handler->addDataTable($legend, $data);
                }
            }

            $run = new Run();
            $run->prependHandler($handler);

            return $response->write($run->handleException($this->exception));
        }

        try {
            $view = $this->factory->make(
                View::class,
                [
                    'request' => $this->request,
                ]
            );

            return $view->renderToResponse(
                $response,
                'system/error_general',
                [
                    'exception' => $this->exception,
                ]
            );
        } catch (Throwable) {
            return parent::respond();
        }
    }
}
