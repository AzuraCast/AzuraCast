<?php
namespace App\Controller\Admin;

use App\Form\SettingsForm;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        if (false !== $this->form->process($request)) {
            \App\Http\RequestHelper::getSession($request)->flash(__('Changes saved.'), 'green');
            return $response->withRedirect($request->getUri()->getPath());
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('System Settings'),
        ]);
    }
}
