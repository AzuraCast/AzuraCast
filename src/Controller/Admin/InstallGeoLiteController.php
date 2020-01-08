<?php
namespace App\Controller\Admin;

use App\Form\GeoLiteSettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class InstallGeoLiteController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        GeoLiteSettingsForm $form
    ): ResponseInterface {
        if (false !== $form->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Install GeoLite IP Database'),
        ]);
    }
}
