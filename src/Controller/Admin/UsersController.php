<?php
namespace App\Controller\Admin;

use App\Auth;
use App\Entity;
use App\Form\UserForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Http\Message\ResponseInterface;

class UsersController extends AbstractAdminCrudController
{
    /** @var Auth */
    protected $auth;

    /**
     * @param UserForm $form
     * @param Auth $auth
     */
    public function __construct(
        UserForm $form,
        Auth $auth
    ) {
        parent::__construct($form);

        $this->auth = $auth;
        $this->csrf_namespace = 'admin_users';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $users = $this->em->createQuery(/** @lang DQL */'SELECT 
            u, r 
            FROM App\Entity\User u 
            LEFT JOIN u.roles r
            ORDER BY u.name ASC')
            ->execute();

        return $request->getView()->renderToResponse($response, 'admin/users/index', [
            'user' => $request->getAttribute('user'),
            'users' => $users,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        try {
            if (false !== $this->_doEdit($request, $id)) {
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

    public function deleteAction(ServerRequest $request, Response $response, $id, $csrf_token): ResponseInterface
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

    public function impersonateAction(ServerRequest $request, Response $response, $id, $csrf_token): ResponseInterface
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
