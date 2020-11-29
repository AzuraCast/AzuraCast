<?php

namespace App\Controller\Frontend\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class TwoFactorAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $auth = $request->getAuth();

        if ($request->isPost()) {
            $flash = $request->getFlash();
            $otp = $request->getParam('otp');

            if ($auth->verifyTwoFactor($otp)) {
                $user = $auth->getUser();

                $flash->addMessage(
                    '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
                    Flash::SUCCESS
                );

                $referrer = $request->getSession()->get('login_referrer');
                if (!empty($referrer)) {
                    return $response->withRedirect($referrer);
                }

                return $response->withRedirect($request->getRouter()->named('dashboard'));
            }

            $flash->addMessage(
                '<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
                Flash::ERROR
            );

            return $response->withRedirect($request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/two_factor');
    }
}
