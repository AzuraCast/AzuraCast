<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\Repository\SettingsRepository;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        // Redirect to complete setup, if it hasn't been completed yet.
        $settings = $this->settingsRepo->readSettings();
        if (!$settings->isSetupComplete()) {
            return $response->withRedirect($request->getRouter()->named('setup:index'));
        }

        // Redirect to login screen if the user isn't logged in.
        $user = $request->getAttribute(ServerRequest::ATTR_USER);

        if (!($user instanceof User)) {
            // Redirect to a custom homepage URL if specified in settings.
            $homepageRedirect = $settings->getHomepageRedirectUrl();
            if (null !== $homepageRedirect) {
                return $response->withRedirect($homepageRedirect, 302);
            }

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }

        // Redirect to dashboard if no other custom redirection rules exist.
        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }
}
