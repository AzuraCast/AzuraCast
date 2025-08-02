<?php

declare(strict_types=1);

namespace App\Http;

use App\AppFactory;
use App\Container\EnvironmentAwareTrait;
use App\Entity\Api\Error;
use App\Enums\SupportedLocales;
use App\Exception;
use App\Exception\Http\NotLoggedInException;
use App\Exception\Http\PermissionDeniedException;
use App\Middleware\InjectSession;
use App\Session\Flash;
use App\View;
use Mezzio\Session\Session;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * @phpstan-import-type AppWithContainer from AppFactory
 */
final class ErrorHandler extends SlimErrorHandler
{
    use EnvironmentAwareTrait;

    private bool $returnJson = false;

    private bool $showDetailed = false;

    private Level $loggerLevel = Level::Error;

    /**
     * @param View $view
     * @param Router $router
     * @param InjectSession $injectSession
     * @param AppWithContainer $app
     * @param Logger $logger
     */
    public function __construct(
        private readonly View $view,
        private readonly Router $router,
        private readonly InjectSession $injectSession,
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
            $this->loggerLevel = Level::Info;
        }

        $this->showDetailed = $this->environment->showDetailedErrors();
        $this->returnJson = $this->shouldReturnJson($request);

        return parent::__invoke($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails);
    }

    protected function determineStatusCode(): int
    {
        if ($this->method === 'OPTIONS') {
            return 200;
        }

        if ($this->exception instanceof Exception || $this->exception instanceof HttpException) {
            $statusCode = $this->exception->getCode();
            if ($statusCode >= 100 && $statusCode < 600) {
                return $statusCode;
            }
        }

        return 500;
    }

    private function shouldReturnJson(ServerRequestInterface $req): bool
    {
        // All API calls return JSON regardless.
        if ($req instanceof ServerRequest && $req->isApi() && $this->environment->isProduction()) {
            return true;
        }

        // Return JSON for all AJAX (XMLHttpRequest) queries.
        $xhr = $req->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if ($xhr || $this->environment->isCli() || $this->environment->isTesting()) {
            return true;
        }

        // Return JSON if "application/json" is in the "Accept" header.
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
            $flatException = FlattenException::createFromThrowable($this->exception);
            $context['trace'] = array_slice($flatException->getTrace(), 0, 5);
        }

        $this->logger->log($this->loggerLevel, $this->exception->getMessage(), $context);
    }

    protected function respond(): ResponseInterface
    {
        if (!function_exists('__')) {
            $locale = SupportedLocales::default();
            $locale->register($this->environment);
        }

        if (!($this->request instanceof ServerRequest)) {
            return parent::respond();
        }

        /** @var Response $response */
        $response = $this->responseFactory->createResponse($this->statusCode);

        // Special handling for cURL requests.
        $ua = $this->request->getHeaderLine('User-Agent');

        if (false !== stripos($ua, 'curl') || false !== stripos($ua, 'Liquidsoap')) {
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

        if ($this->returnJson) {
            $apiResponse = Error::fromException($this->exception, $this->showDetailed);
            return $response->withJson($apiResponse, null, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        if ($this->exception instanceof NotLoggedInException) {
            // Redirect to login page for not-logged-in users.
            $sessionPersistence = $this->injectSession->getSessionPersistence($this->request);

            /** @var Session $session */
            $session = $sessionPersistence->initializeSessionFromRequest($this->request);

            $flash = new Flash($session);
            $flash->error($this->exception->getMessage());

            // Set referrer for login redirection.
            $session->set('login_referrer', $this->request->getUri()->getPath());

            /** @var Response $response */
            $response = $sessionPersistence->persistSession($session, $response);

            return $response->withRedirect($this->router->named('account:login'));
        }

        if ($this->exception instanceof PermissionDeniedException) {
            $sessionPersistence = $this->injectSession->getSessionPersistence($this->request);

            /** @var Session $session */
            $session = $sessionPersistence->initializeSessionFromRequest($this->request);

            $flash = new Flash($session);
            $flash->error($this->exception->getMessage());

            /** @var Response $response */
            $response = $sessionPersistence->persistSession($session, $response);

            // Bounce back to homepage for permission-denied users.
            return $response->withRedirect($this->router->named('home'));
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
            $view = $this->view->withRequest($this->request);

            return $view->renderToResponse(
                $response,
                ($this->exception instanceof HttpException)
                    ? 'system/error_http'
                    : 'system/error_general',
                [
                    'exception' => $this->exception,
                ]
            );
        } catch (Throwable) {
            return parent::respond();
        }
    }
}
