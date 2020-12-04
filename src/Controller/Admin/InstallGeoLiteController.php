<?php

namespace App\Controller\Admin;

use App\Entity\Repository\SettingsTableRepository;
use App\Entity\Settings;
use App\Form\GeoLiteSettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use App\Session\Flash;
use App\Sync\Task\UpdateGeoLiteTask;
use Exception;
use Psr\Http\Message\ResponseInterface;

class InstallGeoLiteController
{
    protected string $csrf_namespace = 'admin_install_geolite';

    public function __invoke(
        ServerRequest $request,
        Response $response,
        GeoLiteSettingsForm $form,
        UpdateGeoLiteTask $syncTask
    ): ResponseInterface {
        if (false !== $form->process($request)) {
            $flash = $request->getFlash();

            try {
                $syncTask->updateDatabase();
                $flash->addMessage(__('Changes saved.'), Flash::SUCCESS);
            } catch (Exception $e) {
                $flash->addMessage(__(
                    'An error occurred while downloading the GeoLite database: %s',
                    $e->getMessage() . ' (' . $e->getFile() . ' L' . $e->getLine() . ')'
                ), Flash::ERROR);
            }

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
        Settings $settings,
        SettingsTableRepository $settingsTableRepo,
        $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $settings->setGeoliteLicenseKey(null);
        $settingsTableRepo->writeSettings($settings);

        @unlink(GeoLite::getDatabasePath());

        $request->getFlash()->addMessage(__('GeoLite database uninstalled.'), Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->fromHere('admin:install_geolite:index'));
    }
}
