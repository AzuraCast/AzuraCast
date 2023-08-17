<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\UserLoginTokenRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use App\Service\Mail;
use Psr\Http\Message\ResponseInterface;

final class ForgotPasswordAction implements SingleActionInterface
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly UserLoginTokenRepository $loginTokenRepo,
        private readonly RateLimit $rateLimit,
        private readonly Mail $mail
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
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Too many forgot password attempts'),
                        __(
                            'You have attempted to reset your password too many times. Please wait '
                            . '30 seconds and try again.'
                        )
                    ),
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $email = $request->getParsedBodyParam('email', '');
            $user = $this->userRepo->findByEmail($email);

            if ($user instanceof User) {
                $email = $this->mail->createMessage();
                $email->to($user->getEmail());

                $email->subject(__('Account Recovery'));

                $loginToken = $this->loginTokenRepo->createToken($user);
                $email->text(
                    $view->render(
                        'mail/forgot',
                        [
                            'token' => (string)$loginToken,
                        ]
                    )
                );

                $this->mail->send($email);
            }

            $flash->success(
                sprintf(
                    '<b>%s</b><br>%s',
                    __('Account recovery e-mail sent.'),
                    __(
                        'If the e-mail address you provided is in the system, check your inbox '
                        . 'for a password reset message.'
                    )
                ),
            );

            return $response->withRedirect($request->getRouter()->named('account:login'));
        }

        return $view->renderToResponse($response, 'frontend/account/forgot');
    }
}
