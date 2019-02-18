<?php
namespace App\Controller\Admin;

use App\Auth;
use App\Form\UserForm;
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

    /** @var Entity\Repository\UserRepository */
    protected $record_repo;

    /** @var Auth */
    protected $auth;

    /** @var UserForm */
    protected $form;

    /** @var string */
    protected $csrf_namespace = 'admin_users';

    /**
     * @param EntityManager $em
     * @param Auth $auth
     * @param UserForm $form
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(
        EntityManager $em,
        Auth $auth,
        UserForm $form)
    {
        $this->em = $em;
        $this->auth = $auth;
        $this->form = $form;

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
        $record = (null !== $id)
            ? $this->record_repo->find((int)$id)
            : null;

        try {
            if (false !== $this->form->process($request, $record)) {
                $request->getSession()->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('User')),
                    'green');

                return $response->withRedirect($request->getRouter()->named('admin:users:index'));
            }
        } catch(UniqueConstraintViolationException $e) {
            $request->getSession()->flash(__('Another user already exists with this e-mail address. Please update the e-mail address.'), 'red');
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('User'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        $current_user = $request->getUser();

        if ($user === $current_user) {
            $request->getSession()->flash('<b>'.__('You cannot delete your own account.').'</b>', 'red');
        } elseif ($user instanceof Entity\User) {
            $this->em->remove($user);
            $this->em->flush();

            $request->getSession()->flash('<b>' . __('%s deleted.', __('User')) . '</b>', 'green');
        }

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
