<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\Form;
use App\Form\SettingsForm;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

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
            $request->getSession()->flash(__('Changes saved.'), 'green');
            return $response->withRedirect($request->getUri()->getPath());
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('System Settings'),
        ]);
    }
}
