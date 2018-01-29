<?php
namespace Controller\Admin;

use Entity\Repository;
use Entity\Settings;
use App\Http\Request;
use App\Http\Response;

class SettingsController extends \AzuraCast\Legacy\Controller
{
    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);

        $form = new \App\Form($this->config->forms->settings->form);

        $existing_settings = $settings_repo->fetchArray(false);
        $form->setDefaults($existing_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();
            unset($data['submit']);

            $settings_repo->setSettings($data);

            $this->alert(_('Changes saved.'), 'green');

            return $response->redirectHere();
        }

        return $this->renderForm($response, $form, 'edit', _('Site Settings'));
    }
}