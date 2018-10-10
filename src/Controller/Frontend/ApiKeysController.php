<?php
namespace App\Controller\Frontend;

use App\Exception\NotFound;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class ApiKeysController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'frontend_api_keys';

    /** @var Entity\Repository\ApiKeyRepository */
    protected $record_repo;

    /** @var array */
    protected $form_config;

    /**
     * @param EntityManager $em
     * @param array $form_config
     * @see \App\Provider\FrontendProvider
     */
    public function __construct(EntityManager $em, array $form_config)
    {
        $this->em = $em;
        $this->form_config = $form_config;

        $this->record_repo = $this->em->getRepository(Entity\ApiKey::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $user = $request->getUser();

        return $request->getView()->renderToResponse($response, 'frontend/api_keys/index', [
            'records' => $user->getApiKeys(),
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        $user = $request->getUser();
        $view = $request->getView();

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

            $request->getSession()->flash(__('%s updated.', __('API Key')), 'green');
            return $response->withRedirect($request->getRouter()->named('api_keys:index'));
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('API Key'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $record = $this->record_repo->findOneBy(['id' => $id, 'user_id' => $user->getId()]);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();
        $this->em->refresh($user);

        $request->getSession()->flash(__('%s deleted.', __('API Key')), 'green');

        return $response->withRedirect($request->getRouter()->named('api_keys:index'));
    }
}
