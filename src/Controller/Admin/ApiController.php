<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\ApiKeyForm;
use App\Form\EntityFormFactory;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ApiController extends AbstractAdminCrudController
{
    public function __construct(
        FactoryInterface $factory
    ) {
        parent::__construct($factory->make(ApiKeyForm::class));
        $this->csrf_namespace = 'admin_api';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $records = $this->em->createQuery(
            <<<'DQL'
                SELECT a, u FROM App\Entity\ApiKey a JOIN a.user u
            DQL
        )->getArrayResult();

        return $request->getView()->renderToResponse($response, 'admin/api/index', [
            'records' => $records,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, string $id): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage(__('API Key updated.'), Flash::SUCCESS);
            return $response->withRedirect((string)$request->getRouter()->named('admin:api:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $this->form,
                'render_mode' => 'edit',
                'title' => __('Edit API Key'),
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $id,
        string $csrf
    ): ResponseInterface {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage(__('API Key deleted.'), Flash::SUCCESS);
        return $response->withRedirect((string)$request->getRouter()->named('admin:api:index'));
    }
}
