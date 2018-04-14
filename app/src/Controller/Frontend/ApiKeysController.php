<?php
namespace Controller\Frontend;

use App\Csrf;
use App\Exception\NotFound;
use App\Flash;
use Doctrine\ORM\EntityManager;
use Entity;
use Slim\Container;
use App\Http\Request;
use App\Http\Response;

class ApiKeysController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Csrf */
    protected $csrf;

    /** @var string */
    protected $csrf_namespace = 'frontend_api_keys';

    /** @var Entity\Repository\ApiKeyRepository */
    protected $record_repo;

    /** @var array */
    protected $form_config;

    public function __construct(EntityManager $em, Flash $flash, Csrf $csrf, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->form_config = $form_config;

        $this->record_repo = $this->em->getRepository(Entity\ApiKey::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'frontend/api_keys/index', [
            'records' => $user->getApiKeys(),
            'csrf' => $this->csrf->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $new_record = false;
            $record = $this->record_repo->findOneBy(['id' => $id, 'user_id' => $user->getId()]);

            if (!($record instanceof Entity\ApiKey)) {
                throw new NotFound(__('%s not found.', __('API Key')));
            }

            $form->populate($this->record_repo->toArray($record, true, true));
        } else {
            $new_record = true;
            $record = null;
        }

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            if ($new_record) {
                $record = new Entity\ApiKey($user);
                list($key_identifier, $key_verifier) = $record->generate();
            }

            $this->record_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();
            $this->em->refresh($user);

            // Render one-time display
            if ($new_record) {
                return $view->renderToResponse($response, 'frontend/api_keys/new_key', [
                    'key_identifier' => $key_identifier,
                    'key_verifier' => $key_verifier,
                ]);
            }

            $this->flash->alert(__('%s updated.', __('API Key')), 'green');
            return $response->redirectToRoute('api_keys:index');
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'header' => __('My Account'),
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('API Key'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $record = $this->record_repo->findOneBy(['id' => $id, 'user_id' => $user->getId()]);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();
        $this->em->refresh($user);

        $this->flash->alert(__('%s deleted.', __('API Key')), 'green');

        return $response->redirectToRoute('api_keys:index');
    }
}