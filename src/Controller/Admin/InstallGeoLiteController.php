<?php
namespace App\Controller\Admin;

use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Form\GeoLiteSettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use Azura\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class InstallGeoLiteController
{
    protected string $csrf_namespace = 'admin_install_geolite';

    public function __invoke(
        ServerRequest $request,
        Response $response,
        GeoLiteSettingsForm $form
    ): ResponseInterface {
        if (false !== $form->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        $version = GeoLite::getVersion();

        return $request->getView()->renderToResponse($response, 'admin/install_geolite/index', [
            'form' => $form,
            'title' => __('Install GeoLite IP Database'),
            'version' => $version,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function uninstallAction(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo,
        $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $settingsRepo->setSetting(Settings::GEOLITE_LICENSE_KEY, '');

        @unlink(GeoLite::getDatabasePath());

        $request->getFlash()->addMessage(__('GeoLite database uninstalled.'), Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->fromHere('admin:install_geolite:index'));
    }
}
