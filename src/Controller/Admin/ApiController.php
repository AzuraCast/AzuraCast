<?php
namespace App\Controller\Admin;

use App\Form\EntityForm;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class ApiController extends AbstractAdminCrudController
{
    /**
     * @param EntityForm $form
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityForm $form)
    {
        parent::__construct($form);
        $this->csrf_namespace = 'admin_api';
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $records = $this->em->createQuery(/** @lang DQL */'SELECT 
            a, u FROM App\Entity\ApiKey a JOIN a.user u')
            ->getArrayResult();

        return $request->getView()->renderToResponse($response, 'admin/api/index', [
            'records' => $records,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getSession()->flash(__('%s updated.', __('API Key')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:api:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('API Key'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        $request->getSession()->flash(__('%s deleted.', __('API Key')), 'green');
        return $response->withRedirect($request->getRouter()->named('admin:api:index'));
    }
}
