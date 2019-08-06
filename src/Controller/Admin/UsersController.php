<?php
namespace App\Controller\Admin;

use App\Auth;
use App\Entity;
use App\Form\EntityForm;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UsersController extends AbstractAdminCrudController
{
    /** @var Auth */
    protected $auth;

    /**
     * @param EntityForm $form
     * @param Auth $auth
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(
        EntityForm $form,
        Auth $auth
    ) {
        parent::__construct($form);

        $this->auth = $auth;
        $this->csrf_namespace = 'admin_users';
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $users = $this->em->createQuery(/** @lang DQL */'SELECT 
            u, r 
            FROM App\Entity\User u 
            LEFT JOIN u.roles r
            ORDER BY u.name ASC')
            ->execute();

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'admin/users/index', [
            'user' => $request->getAttribute('user'),
            'users' => $users,
            'csrf' => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): ResponseInterface
    {
        try {
            if (false !== $this->_doEdit($request, $id)) {
                \App\Http\RequestHelper::getSession($request)->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('User')),
                    'green');

                return $response->withRedirect($request->getRouter()->named('admin:users:index'));
            }
        } catch(UniqueConstraintViolationException $e) {
            \App\Http\RequestHelper::getSession($request)->flash(__('Another user already exists with this e-mail address. Please update the e-mail address.'), 'red');
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('User'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        \App\Http\RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        $current_user = \App\Http\RequestHelper::getUser($request);

        if ($user === $current_user) {
            \App\Http\RequestHelper::getSession($request)->flash('<b>'.__('You cannot delete your own account.').'</b>', 'red');
        } elseif ($user instanceof Entity\User) {
            $this->em->remove($user);
            $this->em->flush();

            \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('User')) . '</b>', 'green');
        }

        return $response->withRedirect($request->getRouter()->named('admin:users:index'));
    }

    public function impersonateAction(Request $request, Response $response, $id, $csrf_token): ResponseInterface
    {
        \App\Http\RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        if (!($user instanceof Entity\User)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('User')));
        }

        $this->auth->masqueradeAsUser($user);

        \App\Http\RequestHelper::getSession($request)->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }
}
