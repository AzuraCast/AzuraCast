<?php

namespace App\Controller\Frontend;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    protected Entity\Repository\SettingsRepository $settings_repo;

    public function __construct(Entity\Repository\SettingsRepository $settings_repo)
    {
        $this->settings_repo = $settings_repo;
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        // Redirect to complete setup, if it hasn't been completed yet.
        if ($this->settings_repo->getSetting(Entity\Settings::SETUP_COMPLETE, 0) === 0) {
            return $response->withRedirect($request->getRouter()->named('setup:index'));
        }

        // Redirect to login screen if the user isn't logged in.
        $user = $request->getAttribute(ServerRequest::ATTR_USER);

        if (!($user instanceof Entity\User)) {
            // Redirect to a custom homepage URL if specified in settings.
            $homepage_redirect = trim($this->settings_repo->getSetting(Entity\Settings::HOMEPAGE_REDIRECT_URL));

            if (!empty($homepage_redirect)) {
                return $response->withRedirect($homepage_redirect, 302);
            }

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }

        // Redirect to dashboard if no other custom redirection rules exist.
        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }
}
