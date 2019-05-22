<?php
namespace App\Controller\Admin;

use App\Form\SettingsForm;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class BrandingController
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

        return $request->getView()->renderToResponse($response, 'admin/branding/index', [
            'form' => $this->form,
        ]);
    }
}
