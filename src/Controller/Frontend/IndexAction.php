<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Exception\InvalidRequestAttribute;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        // Redirect to complete setup, if it hasn't been completed yet.
        $settings = $this->readSettings();
        if (!$settings->isSetupComplete()) {
            return $response->withRedirect($request->getRouter()->named('setup:index'));
        }

        // Redirect to login screen if the user isn't logged in.
        try {
            $request->getUser();

            // Redirect to dashboard if no other custom redirection rules exist.
            return $response->withRedirect($request->getRouter()->named('dashboard'));
        } catch (InvalidRequestAttribute) {
            // Redirect to a custom homepage URL if specified in settings.
            $homepageRedirect = $settings->getHomepageRedirectUrl();
            if (null !== $homepageRedirect) {
                return $response->withRedirect($homepageRedirect, 302);
            }

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }
    }
}
