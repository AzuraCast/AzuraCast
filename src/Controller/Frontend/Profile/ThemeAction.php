<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Enums\SupportedThemes;
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

        $currentTheme = $user->getThemeEnum();
        $newTheme = match ($currentTheme) {
            SupportedThemes::Dark => SupportedThemes::Light,
            default => SupportedThemes::Dark
        };
        $user->setTheme($newTheme->value);

        $em->persist($user);
        $em->flush();

        $referrer = $request->getHeaderLine('Referer');
        return $response->withRedirect(
            $referrer ?: (string)$request->getRouter()->named('dashboard')
        );
    }
}
