<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Controller\SingleActionInterface;
use App\Enums\SupportedLocales;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $supportedLocales = [];
        foreach (SupportedLocales::cases() as $supportedLocale) {
            $supportedLocales[$supportedLocale->value] = $supportedLocale->getLocalName();
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Account',
            id: 'account',
            title: __('My Account'),
            props: [
                'supportedLocales' => $supportedLocales,
            ]
        );
    }
}
