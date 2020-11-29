<?php

namespace App\Controller\Frontend\Profile;

use App\Form\UserProfileForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class EditAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        UserProfileForm $userProfileForm
    ): ResponseInterface {
        if ($userProfileForm->process($request)) {
            $request->getFlash()->addMessage(__('Profile saved!'), Flash::SUCCESS);

            return $response->withRedirect($request->getRouter()->named('profile:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $userProfileForm,
            'render_mode' => 'edit',
            'title' => __('Edit Profile'),
        ]);
    }
}
