<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\HashMap;
use App\Entity\Api\Vue\DashboardGlobals;
use App\Enums\SupportedLocales;
use App\Http\ServerRequest;
use App\Middleware\AbstractMiddleware;
use App\Middleware\Auth\ApiAuth;
use App\Version;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

final class PanelLayout extends AbstractMiddleware
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly Version $version
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $request->getView();
        $customization = $request->getCustomization();
        $auth = $request->getAuth();
        $router = $request->getRouter();

        $settings = $this->readSettings();

        $supportedLocales = [];
        foreach (SupportedLocales::cases() as $supportedLocale) {
            $supportedLocales[$supportedLocale->value] = $supportedLocale->getLocalName();
        }

        $globalProps = $view->getGlobalProps();

        $csrf = $request->getCsrf();
        $globalProps->apiCsrf = $csrf->generate(ApiAuth::API_CSRF_NAMESPACE);

        $globalProps->dashboardProps = new DashboardGlobals(
            $customization->getInstanceName(),
            $router->named('dashboard'),
            ($auth->isMasqueraded())
                ? $router->named('account:endmasquerade')
                : $router->named('account:logout'),
            $this->version->getVersionText(),
            ($this->environment->isDocker() ? 'Docker' : 'Ansible')
            . ' &bull; PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            showCharts: $settings->isAnalyticsEnabled(),
            showAlbumArt: $settings->hide_album_art,
            supportedLocales: new HashMap($supportedLocales)
        );

        return $handler->handle($request);
    }
}
