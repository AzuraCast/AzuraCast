<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Controller\SingleActionInterface;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
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
            $otp = $request->getParam('otp');

            if ($auth->verifyTwoFactor($otp)) {
                /** @var User $user */
                $user = $auth->getUser();

                $flash->success(
                    '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
                );

                $referrer = $request->getSession()->get('login_referrer');
                if (!empty($referrer)) {
                    return $response->withRedirect($referrer);
                }

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            }

            $flash->error(
                '<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
            );

            return $response->withRedirect((string)$request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/two_factor');
    }
}
