<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\User;
use App\Service\SsoService;
use FunctionalTester;

class Public_SsoLoginCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function ssoLoginWithValidToken(FunctionalTester $I): void
    {
        $I->wantTo('Login via SSO with valid token.');

        // Create a test user
        $user = new User();
        $user->email = 'sso-login-test@example.com';
        $user->name = 'SSO Login Test User';
        $user->setNewPassword('password123');
        $this->em->persist($user);
        $this->em->flush();

        // Generate SSO token
        $ssoService = $this->di->get(SsoService::class);
        $token = $ssoService->generateToken(
            userId: $user->id,
            comment: 'Test Login Token',
            expiresIn: 300
        );

        $I->assertNotNull($token);

        // Create the full token string
        $splitToken = new \App\Security\SplitToken();
        $splitToken->identifier = $token->id;
        $splitToken->verifier = $token->verifier;

        // Test SSO login
        $I->sendGET('/sso/login?token=' . urlencode((string) $splitToken));

        $I->seeResponseCodeIs(302); // Redirect to dashboard
        $I->seeHttpHeader('Location', '/dashboard');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function ssoLoginWithInvalidToken(FunctionalTester $I): void
    {
        $I->wantTo('Test SSO login with invalid token.');

        // Test with invalid token
        $I->sendGET('/sso/login?token=invalid-token');

        $I->seeResponseCodeIs(401);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function ssoLoginWithExpiredToken(FunctionalTester $I): void
    {
        $I->wantTo('Test SSO login with expired token.');

        // Create a test user
        $user = new User();
        $user->email = 'sso-expired-test@example.com';
        $user->name = 'SSO Expired Test User';
        $user->setNewPassword('password123');
        $this->em->persist($user);
        $this->em->flush();

        // Generate SSO token with very short expiration
        $ssoService = $this->di->get(SsoService::class);
        $token = $ssoService->generateToken(
            userId: $user->id,
            comment: 'Test Expired Token',
            expiresIn: 1 // 1 second
        );

        $I->assertNotNull($token);

        // Wait for token to expire
        sleep(2);

        // Create the full token string
        $splitToken = new \App\Security\SplitToken();
        $splitToken->identifier = $token->id;
        $splitToken->verifier = $token->verifier;

        // Test SSO login with expired token
        $I->sendGET('/sso/login?token=' . urlencode((string) $splitToken));

        $I->seeResponseCodeIs(401);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function ssoLoginWithMissingToken(FunctionalTester $I): void
    {
        $I->wantTo('Test SSO login without token.');

        // Test without token parameter
        $I->sendGET('/sso/login');

        $I->seeResponseCodeIs(400);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function ssoLoginWithRedirectUrl(FunctionalTester $I): void
    {
        $I->wantTo('Test SSO login with redirect URL.');

        // Create a test user
        $user = new User();
        $user->email = 'sso-redirect-test@example.com';
        $user->name = 'SSO Redirect Test User';
        $user->setNewPassword('password123');
        $this->em->persist($user);
        $this->em->flush();

        // Generate SSO token
        $ssoService = $this->di->get(SsoService::class);
        $token = $ssoService->generateToken(
            userId: $user->id,
            comment: 'Test Redirect Token',
            expiresIn: 300
        );

        $I->assertNotNull($token);

        // Create the full token string
        $splitToken = new \App\Security\SplitToken();
        $splitToken->identifier = $token->id;
        $splitToken->verifier = $token->verifier;

        // Test SSO login with redirect URL
        $I->sendGET('/sso/login?token=' . urlencode((string) $splitToken) . '&redirect=/admin');

        $I->seeResponseCodeIs(302); // Redirect
        $I->seeHttpHeader('Location', '/admin');
    }
}
