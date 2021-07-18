<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity;
use App\Exception\NotFoundException;
use App\Form\UserForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use DI\FactoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Http\Message\ResponseInterface;

class UsersController extends AbstractAdminCrudController
{
    public function __construct(FactoryInterface $factory)
    {
        parent::__construct($factory->make(UserForm::class));

        $this->csrf_namespace = 'admin_users';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $users = $this->em->createQuery(
            <<<'DQL'
                SELECT u, r
                FROM App\Entity\User u
                LEFT JOIN u.roles r
                ORDER BY u.name ASC
            DQL
        )->execute();

        return $request->getView()->renderToResponse($response, 'admin/users/index', [
            'user' => $request->getAttribute('user'),
            'users' => $users,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, int $id = null): ResponseInterface
    {
        try {
            if (false !== $this->doEdit($request, $id)) {
                $request->getFlash()->addMessage(($id ? __('User updated.') : __('User added.')), Flash::SUCCESS);

                return $response->withRedirect((string)$request->getRouter()->named('admin:users:index'));
            }
        } catch (UniqueConstraintViolationException) {
            $request->getFlash()->addMessage(
                __('Another user already exists with this e-mail address. Please update the e-mail address.'),
                Flash::ERROR
            );
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $this->form,
                'render_mode' => 'edit',
                'title' => $id ? __('Edit User') : __('Add User'),
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $id,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $user = $this->record_repo->find($id);

        $current_user = $request->getUser();

        if ($user === $current_user) {
            $request->getFlash()->addMessage('<b>' . __('You cannot delete your own account.') . '</b>', Flash::ERROR);
        } elseif ($user instanceof Entity\User) {
            $this->em->remove($user);
            $this->em->flush();

            $request->getFlash()->addMessage('<b>' . __('User deleted.') . '</b>', Flash::SUCCESS);
        }

        return $response->withRedirect((string)$request->getRouter()->named('admin:users:index'));
    }

    public function impersonateAction(
        ServerRequest $request,
        Response $response,
        int $id,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $user = $this->record_repo->find($id);

        if (!($user instanceof Entity\User)) {
            throw new NotFoundException(__('User not found.'));
        }

        $auth = $request->getAuth();
        $auth->masqueradeAsUser($user);

        $request->getFlash()->addMessage(
            '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
            Flash::SUCCESS
        );

        return $response->withRedirect((string)$request->getRouter()->named('dashboard'));
    }
}
