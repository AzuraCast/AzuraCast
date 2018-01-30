<?php
namespace Controller\Admin;

use App\Flash;
use Entity;
use App\Http\Request;
use App\Http\Response;

class SettingsController
{
    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Flash */
    protected $flash;

    /** @var array */
    protected $form_config;

    /**
     * @param Entity\Repository\SettingsRepository $settings_repo
     * @param Flash $flash
     * @param array $form_config
     */
    public function __construct(Entity\Repository\SettingsRepository $settings_repo, Flash $flash, array $form_config)
    {
        $this->settings_repo = $settings_repo;
        $this->flash = $flash;
        $this->form_config = $form_config;
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $form = new \App\Form($this->form_config);

        $existing_settings = $this->settings_repo->fetchArray(false);
        $form->setDefaults($existing_settings);

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();
            unset($data['submit']);

            $this->settings_repo->setSettings($data);

            $this->flash->alert(_('Changes saved.'), 'green');

            return $response->redirectHere();
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => _('Site Settings')
        ]);
    }
}