<?php

namespace App\Controller\Frontend\Profile;

use App\Form\UserProfileForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class IndexAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        UserProfileForm $userProfileForm
    ): ResponseInterface {
        return $request->getView()->renderToResponse($response, 'frontend/profile/index', [
            'user' => $request->getUser(),
            'profileView' => $userProfileForm->getView($request),
        ]);
    }
}
