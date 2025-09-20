<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\User;
use FunctionalTester;

class Api_Admin_SsoCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function generateSsoToken(FunctionalTester $I): void
    {
        $I->wantTo('Generate SSO token via API.');

        // Create a test user
        $user = new User();
        $user->email = 'sso-test@example.com';
        $user->name = 'SSO Test User';
        $user->setNewPassword('password123');
        $this->em->persist($user);
        $this->em->flush();

        // Test token generation
        $I->sendPOST('/api/admin/sso/generate', [
            'user_id' => $user->id,
            'comment' => 'Test SSO Token',
            'expires_in' => 300,
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseJsonMatchesJsonPath('$.data.token_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.sso_url');
        $I->seeResponseJsonMatchesJsonPath('$.data.user.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.user.email');

        // Verify the response structure
        $response = $I->grabDataFromResponseByJsonPath('$.data')[0];
        $I->assertNotEmpty($response['token_id']);
        $I->assertNotEmpty($response['sso_url']);
        $I->assertEquals($user->id, $response['user']['id']);
        $I->assertEquals($user->email, $response['user']['email']);
        $I->assertGreaterThan(0, $response['expires_in']);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function generateSsoTokenValidation(FunctionalTester $I): void
    {
        $I->wantTo('Test SSO token generation validation.');

        // Test missing user_id
        $I->sendPOST('/api/admin/sso/generate', [
            'comment' => 'Test SSO Token',
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => false]);

        // Test invalid user_id
        $I->sendPOST('/api/admin/sso/generate', [
            'user_id' => 99999,
            'comment' => 'Test SSO Token',
        ]);

        $I->seeResponseCodeIs(404);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => false]);

        // Test invalid expires_in
        $I->sendPOST('/api/admin/sso/generate', [
            'user_id' => 1,
            'expires_in' => 30, // Too short
        ]);

        $I->seeResponseCodeIs(422);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => false]);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function listUserTokens(FunctionalTester $I): void
    {
        $I->wantTo('List SSO tokens for a user.');

        // Create a test user
        $user = new User();
        $user->email = 'sso-list-test@example.com';
        $user->name = 'SSO List Test User';
        $user->setNewPassword('password123');
        $this->em->persist($user);
        $this->em->flush();

        // Generate a token first
        $I->sendPOST('/api/admin/sso/generate', [
            'user_id' => $user->id,
            'comment' => 'Test Token 1',
        ]);
        $I->seeResponseCodeIs(201);

        // List tokens
        $I->sendGET('/api/admin/sso/user/' . $user->id . '/tokens');

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseJsonMatchesJsonPath('$.data[0].id');
        $I->seeResponseJsonMatchesJsonPath('$.data[0].comment');
        $I->seeResponseJsonMatchesJsonPath('$.data[0].is_valid');

        // Verify the response structure
        $response = $I->grabDataFromResponseByJsonPath('$.data')[0];
        $I->assertIsArray($response);
        $I->assertCount(1, $response);
        $I->assertEquals('Test Token 1', $response[0]['comment']);
        $I->assertTrue($response[0]['is_valid']);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function revokeUserTokens(FunctionalTester $I): void
    {
        $I->wantTo('Revoke SSO tokens for a user.');

        // Create a test user
        $user = new User();
        $user->email = 'sso-revoke-test@example.com';
        $user->name = 'SSO Revoke Test User';
        $user->setNewPassword('password123');
        $this->em->persist($user);
        $this->em->flush();

        // Generate a token first
        $I->sendPOST('/api/admin/sso/generate', [
            'user_id' => $user->id,
            'comment' => 'Test Token to Revoke',
        ]);
        $I->seeResponseCodeIs(201);

        // Revoke tokens
        $I->sendDELETE('/api/admin/sso/user/' . $user->id . '/tokens');

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseJsonMatchesJsonPath('$.data.revoked_count');

        // Verify tokens were revoked
        $I->sendGET('/api/admin/sso/user/' . $user->id . '/tokens');
        $I->seeResponseCodeIs(200);
        $response = $I->grabDataFromResponseByJsonPath('$.data')[0];
        $I->assertEmpty($response);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function cleanupExpiredTokens(FunctionalTester $I): void
    {
        $I->wantTo('Cleanup expired SSO tokens.');

        // Test cleanup endpoint
        $I->sendDELETE('/api/admin/sso/cleanup');

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseJsonMatchesJsonPath('$.data.cleaned_count');
    }
}
