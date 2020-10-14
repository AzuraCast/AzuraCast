<?php

namespace App\Controller\Frontend\Profile;

use App\Form\UserProfileForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ThemeAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        UserProfileForm $userProfileForm
    ): ResponseInterface {
        $userProfileForm->switchTheme($request);

        $referrer = $request->getHeaderLine('Referer');
        return $response->withRedirect(
            $referrer ?: (string)$request->getRouter()->named('dashboard')
        );
    }
}
