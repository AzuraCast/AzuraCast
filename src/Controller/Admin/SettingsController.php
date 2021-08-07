<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\SettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class SettingsController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory
    ): ResponseInterface {
        $form = $factory->make(SettingsForm::class);

        if (false !== $form->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $form,
                'render_mode' => 'edit',
                'title' => __('System Settings'),
            ]
        );
    }
}
