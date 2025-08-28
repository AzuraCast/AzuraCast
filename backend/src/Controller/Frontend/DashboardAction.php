<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\HashMap;
use App\Entity\Api\Vue\DashboardGlobals;
use App\Enums\SupportedLocales;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Middleware\Auth\ApiAuth;
use App\Version;
use Psr\Http\Message\ResponseInterface;

final class DashboardAction implements SingleActionInterface
{
    use SettingsAwareTrait;
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly Version $version
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
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
            showAlbumArt: !$settings->hide_album_art,
            supportedLocales: new HashMap($supportedLocales)
        );

        return $view->renderVuePage(
            response: $response,
            component: 'Dashboard',
            id: 'dashboard',
            title: __('Dashboard')
        );
    }
}
