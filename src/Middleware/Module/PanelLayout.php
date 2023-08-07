<?php

declare(strict_types=1);

namespace App\Middleware\Module;

use App\Container\EnvironmentAwareTrait;
use App\Enums\GlobalPermissions;
use App\Http\ServerRequest;
use App\Middleware\Auth\ApiAuth;
use App\Version;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

final class PanelLayout
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly Version $version
    ) {
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $view = $request->getView();
        $customization = $request->getCustomization();
        $user = $request->getUser();
        $auth = $request->getAuth();
        $acl = $request->getAcl();
        $router = $request->getRouter();

        $globalProps = $view->getGlobalProps();

        $csrf = $request->getCsrf();
        $globalProps->set('apiCsrf', $csrf->generate(ApiAuth::API_CSRF_NAMESPACE));

        $globalProps->set('panelProps', [
            'instanceName' => $customization->getInstanceName(),
            'userDisplayName' => $user->getDisplayName(),
            'homeUrl' => $router->named('dashboard'),
            'adminUrl' => $router->named('admin:index:index'),
            'profileUrl' => $router->named('profile:index'),
            'logoutUrl' => ($auth->isMasqueraded())
                ? $router->named('account:endmasquerade')
                : $router->named('account:logout'),
            'showAdmin' => $acl->isAllowed(GlobalPermissions::View),
            'version' => $this->version->getVersionText(),
            'platform' => ($this->environment->isDocker() ? 'Docker' : 'Ansible')
                . ' &bull; PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        ]);

        return $handler->handle($request);
    }
}
