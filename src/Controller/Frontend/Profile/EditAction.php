<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Form\UserProfileForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class EditAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory
    ): ResponseInterface {
        $userProfileForm = $factory->make(UserProfileForm::class);

        if ($userProfileForm->process($request)) {
            $request->getFlash()->addMessage(__('Profile saved!'), Flash::SUCCESS);

            return $response->withRedirect((string)$request->getRouter()->named('profile:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $userProfileForm,
                'render_mode' => 'edit',
                'title' => __('Edit Profile'),
            ]
        );
    }
}
