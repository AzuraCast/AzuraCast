<?php
namespace App\Http;

use App\Acl;
use App\Service\Sentry;
use Azura\View;
use Azura\Session;
use App\Entity;
use Monolog\Logger;

class ErrorHandler extends \Azura\Http\ErrorHandler
{
    /** @var Acl */
    protected $acl;

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
        Acl $acl,
        Session $session,
        Router $router,
        View $view,
        Sentry $sentry
    ) {
        parent::__construct($app, $logger);

        $this->acl = $acl;
        $this->session = $session;
        $this->router = $router;
        $this->view = $view;
        $this->sentry = $sentry;
    }


    /**
     * ErrorHandler constructor.
     * NOTE: Session and View need to be injected directly, as the request attributes don't get
     *       passed when handling middleware exceptions.
     *
     * @param Acl $acl
     * @param Logger $logger
     * @param Session $session
     * @param Router $router
     * @param View $view
     */
    public function __coasdftruct(
        Acl $acl,
        Logger $logger,
        Router $router,
        Session $session,
        View $view,
        Sentry $sentry
    )
    {
        $this->acl = $acl;
        $this->logger = $logger;
        $this->router = $router;
        $this->session = $session;
        $this->view = $view;
        $this->sentry = $sentry;
    }

    public function __invoke(Request $req, Response $res, \Throwable $e)
    {
        if (!function_exists('__')) {
            $translator = new \Gettext\Translator();
            $translator->register();
        }

        // Don't log errors that are internal to the application.
        $e_extra = self::logException($this->logger, $e);
        $show_detailed = isset($e_extra['trace']);

        // Special handling for cURL (i.e. Liquidsoap) requests.
        $ua = $req->getHeaderLine('User-Agent');

        if (false !== stripos($ua, 'curl')) {
            return $res->write('Error: '.$e->getMessage().' on '.$e->getFile().' L'.$e->getLine());
        }

        $return_json = $this->_returnJson($req);

        if ($e instanceof \App\Exception\NotLoggedIn) {
            $error_message = __('You must be logged in to access this page.');

            if ($return_json) {
                return $res
                    ->withStatus(403)
                    ->withJson(new Entity\Api\Error(403, $error_message));
            }

            // Redirect to login page for not-logged-in users.
            $this->session->flash(__('You must be logged in to access this page.'), 'red');

            // Set referrer for login redirection.
            $referrer_login = $this->session->get('login_referrer');
            $referrer_login->url = $req->getUri()->getPath();

            return $res
                ->withStatus(302)
                ->withHeader('Location', $this->router->named('account:login'));
        }

        if ($e instanceof \App\Exception\PermissionDenied) {
            $error_message = __('You do not have permission to access this portion of the site.');

            if ($return_json) {
                return $res
                    ->withStatus(403)
                    ->withJson(new Entity\Api\Error(403, $error_message));
            }

            // Bounce back to homepage for permission-denied users.
            $this->session->flash(__('You do not have permission to access this portion of the site.'),
                Session\Flash::ERROR);

            return $res
                ->withStatus(302)
                ->withHeader('Location', $this->router->named('home'));
        }

        $this->sentry->handleException($e);

        if ($return_json) {
            $api_response = new Entity\Api\Error(
                $e->getCode(),
                $e->getMessage(),
                ($e instanceof \Azura\Exception) ? $e->getFormattedMessage() : $e->getMessage(),
                $e_extra
            );

            return $res
                ->withStatus(500)
                ->withJson($api_response);
        }

        if ($show_detailed) {
            // Register error-handler.
            $handler = new \Whoops\Handler\PrettyPageHandler;
            $handler->setPageTitle('An error occurred!');

            if ($e instanceof \Azura\Exception) {
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

    protected function _returnJson(Request $req): bool
    {
        if (APP_IS_COMMAND_LINE || APP_TESTING_MODE || $req->isXhr()) {
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

    /**
     * @param Logger $logger
     * @param \Throwable $e
     * @param bool|null $include_trace
     * @return array The logging context.
     */
    public static function logException(Logger $logger, \Throwable $e, $include_trace = null): array
    {
        $e_level = ($e instanceof \Azura\Exception)
            ? $e->getLoggerLevel()
            : Logger::ERROR;

        if (null === $include_trace) {
            $include_trace = !APP_IN_PRODUCTION && $e_level >= Logger::ERROR;
        }

        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
        ];

        if ($e instanceof \Azura\Exception) {
            $context['context'] = $e->getLoggingContext();
            $context = array_merge($context, $e->getExtraData());
        }

        if ($include_trace) {
            $context['trace'] = array_slice($e->getTrace(), 0, 5);
        }

        $logger->addRecord($e_level, $e->getMessage(), $context);
        return $context;
    }
}
