<?php
namespace App\Controller\Admin;

use App\Auth;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class UsersController
{
    /** @var EntityManager */
    protected $em;

    /** @var Auth */
    protected $auth;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\UserRepository */
    protected $record_repo;

    /** @var string */
    protected $csrf_namespace = 'admin_users';

    /**
     * @param EntityManager $em
     * @param Auth $auth
     * @param array $form_config
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityManager $em, Auth $auth, array $form_config)
    {
        $this->em = $em;
        $this->auth = $auth;
        $this->form_config = $form_config;

        $this->record_repo = $this->em->getRepository(Entity\User::class);
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $users = $this->em->createQuery('SELECT u, r FROM '.Entity\User::class.' u LEFT JOIN u.roles r ORDER BY u.name ASC')
            ->execute();

        return $request->getView()->renderToResponse($response, 'admin/users/index', [
            'user' => $request->getAttribute('user'),
            'users' => $users,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): ResponseInterface
    {
        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $record = $this->record_repo->find((int)$id);
            $record_defaults = $this->record_repo->toArray($record, true, true);

            unset($record_defaults['auth_password']);

            $form->populate($record_defaults);
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\User)) {
                $record = new Entity\User;
            }

            $this->record_repo->fromArray($record, $data);

            try {
                $this->em->persist($record);
                $this->em->flush();

                $request->getSession()->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('User')), 'green');

                return $response->withRedirect($request->getRouter()->named('admin:users:index'));
            } catch(UniqueConstraintViolationException $e) {
                $request->getSession()->flash(__('Another user already exists with this e-mail address. Please update the e-mail address.'), 'red');
            }
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('User'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        if ($user instanceof Entity\User) {
            $this->em->remove($user);
        }

        $this->em->flush();

        $request->getSession()->flash('<b>' . __('%s deleted.', __('User')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->named('admin:users:index'));
    }

    public function impersonateAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        if (!($user instanceof Entity\User)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('User')));
        }

        $this->auth->masqueradeAsUser($user);

        $request->getSession()->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }
}
