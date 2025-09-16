<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Controller\SingleActionInterface;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class TwoFactorAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $auth = $request->getAuth();

        if ($request->isPost()) {
            $flash = $request->getFlash();
            $otp = Types::string($request->getParam('otp'));

            if ($auth->verifyTwoFactor($otp)) {
                /** @var User $user */
                $user = $auth->getUser();

                $flash->success(
                    message: $user->email,
                    title: __('Logged in successfully.'),
                );

                $referrer = Types::stringOrNull($request->getSession()->get('login_referrer'), true);
                return $response->withRedirect(
                    $referrer ?? $request->getRouter()->named('dashboard')
                );
            }

            $flash->error(
                message: __('Your credentials could not be verified.'),
                title: __('Login unsuccessful')
            );

            return $response->withRedirect((string)$request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/two_factor');
    }
}
