<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\LoginTokenTypes;
use App\Entity\Repository\UserLoginTokenRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\Http\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use App\Service\Mail;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final readonly class ForgotPasswordAction implements SingleActionInterface
{
    public function __construct(
        private UserRepository $userRepo,
        private UserLoginTokenRepository $loginTokenRepo,
        private RateLimit $rateLimit,
        private Mail $mail
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $flash = $request->getFlash();
        $view = $request->getView();

        if (!$this->mail->isEnabled()) {
            return $view->renderToResponse($response, 'frontend/account/forgot_disabled');
        }

        if ($request->isPost()) {
            try {
                $this->rateLimit->checkRequestRateLimit($request, 'forgot', 30, 3);
            } catch (RateLimitExceededException) {
                $flash->error(
                    message: __(
                        'You have attempted to reset your password too many times. Please wait '
                        . '30 seconds and try again.'
                    ),
                    title: __('Too many forgot password attempts'),
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $email = Types::string($request->getParsedBodyParam('email'));
            $user = $this->userRepo->findByEmail($email);

            if ($user instanceof User) {
                $email = $this->mail->createMessage();
                $email->to($user->email);

                $email->subject(__('Account Recovery'));

                [$token] = $this->loginTokenRepo->createToken(
                    $user,
                    LoginTokenTypes::ResetPassword,
                    'Self-service password reset'
                );

                $router = $request->getRouter();
                $url = $router->named(
                    routeName: 'account:login-token',
                    routeParams: ['token' => $token],
                    absolute: true
                );

                $email->text(
                    $view->render(
                        'mail/forgot',
                        [
                            'url' => $url,
                        ]
                    )
                );

                $this->mail->send($email);
            }

            $flash->success(
                message: __(
                    'If the e-mail address you provided is in the system, check your inbox '
                    . 'for a password reset message.'
                ),
                title: __('Account recovery e-mail sent.'),
            );

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }

        return $view->renderToResponse($response, 'frontend/account/forgot');
    }
}
