<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\UserLoginTokenRepository;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class RecoverAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly UserLoginTokenRepository $loginTokenRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $token */
        $token = $params['token'];

        $user = $this->loginTokenRepo->authenticate($token);
        $flash = $request->getFlash();

        if (!$user instanceof User) {
            $flash->error(
                sprintf(
                    '<b>%s</b>',
                    __('Invalid token specified.'),
                ),
            );

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }

        $csrf = $request->getCsrf();
        $error = null;

        if ($request->isPost()) {
            try {
                $data = $request->getParams();

                $csrf->verify($data['csrf'] ?? null, 'recover');

                if (empty($data['password'])) {
                    throw new InvalidArgumentException('Password required.');
                }

                $user->setNewPassword($data['password']);
                $user->setTwoFactorSecret();

                $this->em->persist($user);
                $this->em->flush();

                $request->getAuth()->setUser($user);

                $this->loginTokenRepo->revokeForUser($user);

                $flash->success(
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Logged in using account recovery token'),
                        __('Your password has been updated.')
                    ),
                );

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Recover',
            id: 'account-recover',
            layout: 'minimal',
            title: __('Recover Account'),
            props: [
                'csrf' => $csrf->generate('recover'),
                'error' => $error,
            ]
        );
    }
}
