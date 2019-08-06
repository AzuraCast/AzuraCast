<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\EntityFormManager;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiController extends AbstractAdminCrudController
{
    /**
     * @param Config $config
     * @param EntityFormManager $formManager
     */
    public function __construct(
        Config $config,
        EntityFormManager $formManager
    ) {
        $form = $formManager->getForm(Entity\ApiKey::class , $config->get('forms/api_key'));

        parent::__construct($form);
        $this->csrf_namespace = 'admin_api';
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $records = $this->em->createQuery(/** @lang DQL */'SELECT 
            a, u FROM App\Entity\ApiKey a JOIN a.user u')
            ->getArrayResult();

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'admin/api/index', [
            'records' => $records,
            'csrf' => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            \App\Http\RequestHelper::getSession($request)->flash(__('%s updated.', __('API Key')), 'green');
            return $response->withRedirect($request->getRouter()->named('admin:api:index'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('API Key'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        \App\Http\RequestHelper::getSession($request)->flash(__('%s deleted.', __('API Key')), 'green');
        return $response->withRedirect($request->getRouter()->named('admin:api:index'));
    }
}
