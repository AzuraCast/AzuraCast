<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Form\EntityFormManager;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $records = $this->em->createQuery(/** @lang DQL */'SELECT 
            a, u FROM App\Entity\ApiKey a JOIN a.user u')
            ->getArrayResult();

        return RequestHelper::getView($request)->renderToResponse($response, 'admin/api/index', [
            'records' => $records,
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $id): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            RequestHelper::getSession($request)->flash(__('%s updated.', __('API Key')), 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:api:index'));
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('API Key'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $id, $csrf_token): ResponseInterface
    {
        $this->_doDelete($request, $id, $csrf_token);

        RequestHelper::getSession($request)->flash(__('%s deleted.', __('API Key')), 'green');
        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:api:index'));
    }
}
