<?php
namespace App\Controller\Admin;

use App\Form\SettingsForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Settings;
use Azura\Config;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

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

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        if (false !== $this->form->process($request)) {
            $request->getSession()->flash(__('Changes saved.'), Flash::SUCCESS);
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse($response, 'admin/branding/index', [
            'form' => $this->form,
        ]);
    }
}
