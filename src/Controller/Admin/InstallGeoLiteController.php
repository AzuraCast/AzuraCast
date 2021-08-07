<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Repository\SettingsRepository;
use App\Form\GeoLiteSettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocator\GeoLite;
use App\Session\Flash;
use App\Sync\Task\UpdateGeoLiteTask;
use DI\FactoryInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;

class InstallGeoLiteController
{
    protected string $csrf_namespace = 'admin_install_geolite';

    public function __invoke(
        ServerRequest $request,
        Response $response,
        FactoryInterface $factory,
        UpdateGeoLiteTask $syncTask
    ): ResponseInterface {
        $form = $factory->make(GeoLiteSettingsForm::class);

        if (false !== $form->process($request)) {
            $flash = $request->getFlash();

            try {
                $settings = $form->getSettingsRepository()->readSettings();
                $syncTask->updateDatabase($settings->getGeoliteLicenseKey() ?? '');
                $flash->addMessage(__('Changes saved.'), Flash::SUCCESS);
            } catch (Exception $e) {
                $flash->addMessage(
                    __(
                        'An error occurred while downloading the GeoLite database: %s',
                        $e->getMessage() . ' (' . $e->getFile() . ' L' . $e->getLine() . ')'
                    ),
                    Flash::ERROR
                );
            }

            return $response->withRedirect($request->getUri()->getPath());
        }

        $version = GeoLite::getVersion();

        return $request->getView()->renderToResponse(
            $response,
            'admin/install_geolite/index',
            [
                'form' => $form,
                'title' => __('Install GeoLite IP Database'),
                'version' => $version,
                'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
            ]
        );
    }

    public function uninstallAction(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $settings = $settingsRepo->readSettings();
        $settings->setGeoliteLicenseKey(null);
        $settingsRepo->writeSettings($settings);

        @unlink(GeoLite::getDatabasePath());

        $request->getFlash()->addMessage(__('GeoLite database uninstalled.'), Flash::SUCCESS);
        return $response->withRedirect((string)$request->getRouter()->fromHere('admin:install_geolite:index'));
    }
}
