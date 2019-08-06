<?php
namespace App\Controller\Admin;

use App\Form\SettingsForm;
use Azura\Config;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BrandingController
{
    /** @var SettingsForm */
    protected $form;

    /**
     * @param EntityManager $em
     * @param Config $config
     * @param Settings $settings
     */
    public function __construct(
        EntityManager $em,
        Config $config,
        Settings $settings
    ) {
        $form_config = $config->get('forms/branding', ['settings' => $settings]);
        $this->form = new SettingsForm($em, $form_config);
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        if (false !== $this->form->process($request)) {
            \App\Http\RequestHelper::getSession($request)->flash(__('Changes saved.'), 'green');
            return $response->withRedirect($request->getUri()->getPath());
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'admin/branding/index', [
            'form' => $this->form,
        ]);
    }
}
