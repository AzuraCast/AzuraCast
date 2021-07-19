<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Customization;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ThemeAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em
    ): ResponseInterface {
        $user = $request->getUser();

        $currentTheme = $user->getTheme();
        if (empty($currentTheme)) {
            $currentTheme = Customization::DEFAULT_THEME;
        }

        $user->setTheme(
            (Customization::THEME_LIGHT === $currentTheme)
                ? Customization::THEME_DARK
                : Customization::THEME_LIGHT
        );

        $em->persist($user);
        $em->flush();

        $referrer = $request->getHeaderLine('Referer');
        return $response->withRedirect(
            $referrer ?: (string)$request->getRouter()->named('dashboard')
        );
    }
}
