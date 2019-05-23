<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\Form;
use App\Form\SettingsForm;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class SettingsController
{
    /** @var SettingsForm */
    protected $form;

    /**
     * @param SettingsForm $form
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(SettingsForm $form)
    {
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
