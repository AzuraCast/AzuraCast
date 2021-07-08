<?php

namespace App\Controller\Admin;

use App\Form\BrandingSettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class BrandingController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory
    ): ResponseInterface {
        $form = $factory->make(BrandingSettingsForm::class);

        if (false !== $form->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse(
            $response,
            'admin/branding/index',
            [
                'form' => $form,
            ]
        );
    }
}
