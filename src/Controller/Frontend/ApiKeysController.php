<?php
namespace App\Controller\Frontend;

use App\Entity;
use App\Exception\NotFound;
use App\Form\Form;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Azura\Config;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param Config $config
     */
    public function __construct(EntityManager $em, Config $config)
    {
        $this->em = $em;
        $this->form_config = $config->get('forms/api_key');

        $this->record_repo = $this->em->getRepository(Entity\ApiKey::class);
    }

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = RequestHelper::getUser($request);

        return RequestHelper::getView($request)->renderToResponse($response, 'frontend/api_keys/index', [
            'records' => $user->getApiKeys(),
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $id = null): ResponseInterface
    {
        $user = RequestHelper::getUser($request);
        $view = RequestHelper::getView($request);

        $form = new Form($this->form_config);

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

            // Setting values here to avoid static analysis errors.
            $key_identifier = null;
            $key_verifier = null;

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

            RequestHelper::getSession($request)->flash(__('%s updated.', __('API Key')), 'green');
            return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('api_keys:index'));
        }

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('API Key'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $id, $csrf_token): ResponseInterface
    {
        RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $record = $this->record_repo->findOneBy(['id' => $id, 'user_id' => $user->getId()]);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();
        $this->em->refresh($user);

        RequestHelper::getSession($request)->flash(__('%s deleted.', __('API Key')), 'green');

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('api_keys:index'));
    }
}
