<?php
namespace App\Controller\Admin;

use App\Entity\Repository\SettingsRepository;
use App\Form\SettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Config;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class SettingsController
{
    /** @var SettingsForm */
    protected $form;

    /**
     * SettingsController constructor.
     *
     * @param Config $config
     * @param EntityManager $em
     * @param SettingsRepository $settingsRepo
     */
    public function __construct(Config $config, EntityManager $em, SettingsRepository $settingsRepo)
    {
        $form = new SettingsForm($em, $settingsRepo, $config->get('forms/settings'));
        $this->form = $form;
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        if (false !== $this->form->process($request)) {
            $request->getFlash()->addMessage(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('System Settings'),
        ]);
    }
}
