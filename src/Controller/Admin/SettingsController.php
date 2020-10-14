<?php

namespace App\Controller\Admin;

use App\Form\SettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class SettingsController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsForm $form
    ): ResponseInterface {
        if (false !== $form->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('System Settings'),
        ]);
    }
}
