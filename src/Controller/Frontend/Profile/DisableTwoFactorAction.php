<?php

namespace App\Controller\Frontend\Profile;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class DisableTwoFactorAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em
    ): ResponseInterface {
        $user = $request->getUser();

        $user->setTwoFactorSecret(null);

        $em->persist($user);
        $em->flush();

        $request->getFlash()->addMessage(__('Two-factor authentication disabled.'), Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->named('profile:index'));
    }
}
