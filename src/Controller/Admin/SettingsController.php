<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Http\Request;
use App\Http\Response;

class SettingsController
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var array */
    protected $form_config;

    /**
     * @param Entity\Repository\SettingsRepository $settings_repo
     * @param array $form_config
     */
    public function __construct(Entity\Repository\SettingsRepository $settings_repo, array $form_config)
    {
        $this->settings_repo = $settings_repo;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        return $this->renderSettingsForm($request, $response, 'system/form_page');
    }

    protected function renderSettingsForm(Request $request, Response $response, $form_template): Response
    {
        $existing_settings = $this->settings_repo->fetchArray(false);
        $form = new \AzuraForms\Form($this->form_config, $existing_settings);

        if ($request->isPost() && $form->isValid($_POST)) {
            $data = $form->getValues();

            $this->settings_repo->setSettings($data);

            $request->getSession()->flash(__('Changes saved.'), 'green');

            return $response->redirectHere();
        }

        return $request->getView()->renderToResponse($response, $form_template, [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('System Settings'),
        ]);
    }
}
