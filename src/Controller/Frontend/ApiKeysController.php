<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Config;
use App\Entity;
use App\Exception\NotFoundException;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Security\SplitToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ApiKeysController
{
    protected string $csrf_namespace = 'frontend_api_keys';

    protected array $form_config;

    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\ApiKeyRepository $record_repo,
        Config $config
    ) {
        $this->form_config = $config->get('forms/api_key');
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();

        return $request->getView()->renderToResponse($response, 'frontend/api_keys/index', [
            'records' => $user->getApiKeys(),
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, string $id = null): ResponseInterface
    {
        $user = $request->getUser();
        $view = $request->getView();

        $form = new Form($this->form_config);

        if (!empty($id)) {
            $new_record = false;
            $record = $this->record_repo->getRepository()->findOneBy(['id' => $id, 'user_id' => $user->getId()]);

            if (!($record instanceof Entity\ApiKey)) {
                throw new NotFoundException(__('API Key not found.'));
            }

            $form->populate($this->record_repo->toArray($record, true, true));
        } else {
            $new_record = true;
            $record = null;
        }

        if ($form->isValid($request)) {
            $data = $form->getValues();

            $key = null;
            if ($new_record) {
                $key = SplitToken::generate();
                $record = new Entity\ApiKey($user, $key);
            }

            $this->record_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();
            $this->em->refresh($user);

            // Render one-time display
            if ($new_record) {
                return $view->renderToResponse($response, 'frontend/api_keys/new_key', [
                    'key' => (string)$key,
                ]);
            }

            $request->getFlash()->addMessage(__('API Key updated.'), 'green');
            return $response->withRedirect((string)$request->getRouter()->named('api_keys:index'));
        }

        return $view->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $form,
                'render_mode' => 'edit',
                'title' => $id ? __('Edit API Key') : __('Add API Key'),
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $id,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $record = $this->record_repo->getRepository()->findOneBy(['id' => $id, 'user' => $user]);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();
        $this->em->refresh($user);

        $request->getFlash()->addMessage(__('API Key deleted.'), 'green');

        return $response->withRedirect((string)$request->getRouter()->named('api_keys:index'));
    }
}
