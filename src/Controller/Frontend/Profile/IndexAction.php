<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Form\UserProfileForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Avatar;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class IndexAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Avatar $avatar,
        FactoryInterface $factory
    ): ResponseInterface {
        // Avatars
        $avatarService = $avatar->getAvatarService();

        $userProfileForm = $factory->make(UserProfileForm::class);

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
