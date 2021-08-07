<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class IndexAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\SettingsRepository $settingsRepo
    ): ResponseInterface {
        // Redirect to complete setup, if it hasn't been completed yet.
        $settings = $settingsRepo->readSettings();
        if (!$settings->isSetupComplete()) {
            return $response->withRedirect((string)$request->getRouter()->named('setup:index'));
        }

        // Redirect to login screen if the user isn't logged in.
        $user = $request->getAttribute(ServerRequest::ATTR_USER);

        if (!($user instanceof Entity\User)) {
            // Redirect to a custom homepage URL if specified in settings.
            $homepage_redirect = trim($settings->getHomepageRedirectUrl() ?? '');

            if (!empty($homepage_redirect)) {
                return $response->withRedirect($homepage_redirect, 302);
            }

            return $response->withRedirect((string)$request->getRouter()->named('account:login'));
        }

        // Redirect to dashboard if no other custom redirection rules exist.
        return $response->withRedirect((string)$request->getRouter()->named('dashboard'));
    }
}
