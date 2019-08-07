<?php
namespace App\Controller\Admin;

use App\Auth;
use App\Entity;
use App\Form\UserForm;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $users = $this->em->createQuery(/** @lang DQL */'SELECT 
            u, r 
            FROM App\Entity\User u 
            LEFT JOIN u.roles r
            ORDER BY u.name ASC')
            ->execute();

        return RequestHelper::getView($request)->renderToResponse($response, 'admin/users/index', [
            'user' => $request->getAttribute('user'),
            'users' => $users,
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(ServerRequestInterface $request, ResponseInterface $response, $id = null): ResponseInterface
    {
        try {
            if (false !== $this->_doEdit($request, $id)) {
                RequestHelper::getSession($request)->flash(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('User')),
                    'green');

                return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:users:index'));
            }
        } catch(UniqueConstraintViolationException $e) {
            RequestHelper::getSession($request)->flash(__('Another user already exists with this e-mail address. Please update the e-mail address.'), 'red');
        }

        return RequestHelper::getView($request)->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('User'))
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $id, $csrf_token): ResponseInterface
    {
        RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        $current_user = RequestHelper::getUser($request);

        if ($user === $current_user) {
            RequestHelper::getSession($request)->flash('<b>'.__('You cannot delete your own account.').'</b>', 'red');
        } elseif ($user instanceof Entity\User) {
            $this->em->remove($user);
            $this->em->flush();

            RequestHelper::getSession($request)->flash('<b>' . __('%s deleted.', __('User')) . '</b>', 'green');
        }

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('admin:users:index'));
    }

    public function impersonateAction(ServerRequestInterface $request, ResponseInterface $response, $id, $csrf_token): ResponseInterface
    {
        RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        if (!($user instanceof Entity\User)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('User')));
        }

        $this->auth->masqueradeAsUser($user);

        RequestHelper::getSession($request)->flash('<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->named('dashboard'));
    }
}
