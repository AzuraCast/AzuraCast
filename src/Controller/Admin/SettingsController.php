<?php
namespace App\Controller\Admin;

use App\Form\SettingsForm;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SettingsController
{
    /** @var SettingsForm */
    protected $form;

    /**
     * SettingsController constructor.
     * @param Config $config
     * @param EntityManager $em
     */
    public function __construct(Config $config, EntityManager $em)
    {
        $form = new SettingsForm($em, $config->get('forms/settings'));
        $this->form = $form;
    }

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (false !== $this->form->process($request)) {
            RequestHelper::getSession($request)->flash(__('Changes saved.'), 'green');
            return ResponseHelper::withRedirect($response, $request->getUri()->getPath());
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('System Settings'),
        ]);
    }
}
