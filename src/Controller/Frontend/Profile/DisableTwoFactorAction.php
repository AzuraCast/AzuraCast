<?php
namespace App\Controller\Frontend\Profile;

use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class DisableTwoFactorAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em
    ): ResponseInterface {
        $user = $request->getUser();

        $user->setTwoFactorSecret(null);

        $em->persist($user);
        $em->flush($user);

        $request->getFlash()->addMessage(__('Two-factor authentication disabled.'), Flash::SUCCESS);

        return $response->withRedirect($request->getRouter()->named('profile:index'));
    }
}