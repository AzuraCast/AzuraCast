<?php
namespace Controller\Admin;

use Entity\Repository;
use Entity\Settings;
use Slim\Http\Request;
use Slim\Http\Response;

class BrandingController extends BaseController
{
    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Settings::class);

        $cleanup_filter = function($val) {
            return strip_tags($val);
        };

        $form_config = $this->config->forms->branding->toArray();
        foreach($form_config['elements'] as $element_key => $element_info) {
            if (substr($element_key, 0, 10) === 'custom_css') {
                $form_config['elements'][$element_key][1]['filter'] = $cleanup_filter;
            }
        }

        $form = new \App\Form($form_config);

        $existing_settings = $settings_repo->fetchArray(false);
        $form->setDefaults($existing_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();
            unset($data['submit']);

            $settings_repo->setSettings($data);

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectHere($response);
        }

        $this->view->form = $form;
        return $this->render($response, 'admin/branding/index');
    }
}