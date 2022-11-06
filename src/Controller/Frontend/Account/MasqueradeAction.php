<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Entity;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

final class MasqueradeAction
{
    public const CSRF_NAMESPACE = 'user_masquerade';

    public function __construct(
        private readonly Entity\Repository\UserRepository $userRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $id,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, self::CSRF_NAMESPACE);

        $user = $this->userRepo->find($id);

        if (!($user instanceof Entity\User)) {
            throw new NotFoundException(__('User not found.'));
        }

        $auth = $request->getAuth();
        $auth->masqueradeAsUser($user);

        $request->getFlash()->addMessage(
            '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
            Flash::SUCCESS
        );

        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }
}
