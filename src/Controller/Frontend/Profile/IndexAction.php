<?php

namespace App\Controller\Frontend\Profile;

use App\Form\UserProfileForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Avatar;
use Psr\Http\Message\ResponseInterface;

class IndexAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Avatar $avatar,
        UserProfileForm $userProfileForm
    ): ResponseInterface {
        // Avatars
        $avatarService = $avatar->getAvatarService();

        return $request->getView()->renderToResponse(
            $response,
            'frontend/profile/index',
            [
                'user' => $request->getUser(),
                'avatar' => $avatar->getAvatar($request->getUser()->getEmail(), 64),
                'avatarServiceUrl' => $avatarService->getServiceUrl(),
                'profileView' => $userProfileForm->getView($request),
            ]
        );
    }
}
