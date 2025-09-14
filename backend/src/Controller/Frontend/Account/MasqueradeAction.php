<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class MasqueradeAction implements SingleActionInterface
{
    public const string CSRF_NAMESPACE = 'user_masquerade';

    public function __construct(
        private readonly UserRepository $userRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = Types::string($params['id']);
        $csrf = Types::string($params['csrf']);

        $request->getCsrf()->verify($csrf, self::CSRF_NAMESPACE);

        $user = $this->userRepo->find($id);

        if (!($user instanceof User)) {
            throw new NotFoundException(__('User not found.'));
        }

        $auth = $request->getAuth();
        $auth->masqueradeAsUser($user);

        $request->getFlash()->success(
            message: $user->email,
            title: __('Logged in successfully.')
        );

        return $response->withRedirect($request->getRouter()->named('dashboard'));
    }
}
