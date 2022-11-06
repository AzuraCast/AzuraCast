<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Enums\SupportedLocales;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        $supportedLocales = [];
        foreach (SupportedLocales::cases() as $supportedLocale) {
            $supportedLocales[$supportedLocale->value] = $supportedLocale->getLocalName();
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_Account',
            id: 'account',
            title: __('My Account'),
            props: [
                'userUrl' => $router->named('api:frontend:account:me'),
                'changePasswordUrl' => $router->named('api:frontend:account:password'),
                'twoFactorUrl' => $router->named('api:frontend:account:two-factor'),
                'apiKeysApiUrl' => $router->named('api:frontend:api-keys'),
                'supportedLocales' => $supportedLocales,
            ]
        );
    }
}
