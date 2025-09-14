<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Repository\SsoTokenRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\SsoToken;
use App\Entity\User;
use App\Http\RouterInterface;
use App\Security\SplitToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class SsoService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SsoTokenRepository $ssoTokenRepo,
        private readonly UserRepository $userRepo,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * Generate a new SSO token for a user.
     * 
     * @return array{token: SsoToken, tokenString: string}|null
     */
    public function generateToken(
        int $userId,
        string $comment = '',
        int $expiresIn = 300,
        ?string $ipAddress = null
    ): ?array {
        try {
            $user = $this->userRepo->find($userId);
            if (!$user instanceof User) {
                $this->logger->warning('SSO token generation failed: User not found', [
                    'user_id' => $userId,
                ]);
                return null;
            }

            // Clean up any existing tokens for this user to prevent accumulation
            $this->cleanupUserTokens($user);

            // Generate the token first to get the string
            $splitToken = SplitToken::generate();
            $token = $this->ssoTokenRepo->createToken(
                user: $user,
                comment: $comment,
                expiresIn: $expiresIn,
                ipAddress: $ipAddress,
                token: $splitToken
            );

            $this->logger->info('SSO token generated successfully', [
                'user_id' => $userId,
                'user_email' => $user->email,
                'token_id' => $token->id,
                'expires_at' => $token->expires_at,
                'ip_address' => $ipAddress,
            ]);

            return [
                'token' => $token,
                'tokenString' => (string) $splitToken,
            ];
        } catch (\Exception $e) {
            $this->logger->error('SSO token generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Validate and consume an SSO token.
     */
    public function validateAndConsumeToken(string $tokenString): ?User
    {
        try {
            $token = $this->ssoTokenRepo->findValidToken($tokenString);
            if (!$token instanceof SsoToken) {
                $this->logger->warning('SSO token validation failed: Invalid token', [
                    'token_string' => substr($tokenString, 0, 8) . '...',
                ]);
                return null;
            }

            // Mark token as used (delete it)
            $this->ssoTokenRepo->markTokenAsUsed($token);

            $this->logger->info('SSO token consumed successfully', [
                'user_id' => $token->user->id,
                'user_email' => $token->user->email,
                'token_id' => $token->id,
                'comment' => $token->comment,
            ]);

            return $token->user;
        } catch (\Exception $e) {
            $this->logger->error('SSO token validation failed', [
                'token_string' => substr($tokenString, 0, 8) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get active tokens for a user.
     *
     * @return SsoToken[]
     */
    public function getUserTokens(int $userId): array
    {
        $user = $this->userRepo->find($userId);
        if (!$user instanceof User) {
            return [];
        }

        return $this->ssoTokenRepo->getActiveTokensForUser($user);
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeUserTokens(int $userId): int
    {
        $user = $this->userRepo->find($userId);
        if (!$user instanceof User) {
            return 0;
        }

        $tokens = $this->ssoTokenRepo->getActiveTokensForUser($user);
        $count = count($tokens);

        foreach ($tokens as $token) {
            $this->em->remove($token);
        }

        $this->em->flush();

        $this->logger->info('SSO tokens revoked for user', [
            'user_id' => $userId,
            'user_email' => $user->email,
            'token_count' => $count,
        ]);

        return $count;
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int
    {
        $count = $this->ssoTokenRepo->cleanupExpiredTokens();

        if ($count > 0) {
            $this->logger->info('Expired SSO tokens cleaned up', [
                'token_count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Get count of expired tokens without deleting them.
     */
    public function getExpiredTokenCount(): int
    {
        return $this->ssoTokenRepo->getExpiredTokenCount();
    }

    /**
     * Clean up existing tokens for a user to prevent accumulation.
     */
    private function cleanupUserTokens(User $user): void
    {
        $existingTokens = $this->ssoTokenRepo->getActiveTokensForUser($user);
        
        // Keep only the 5 most recent tokens
        if (count($existingTokens) >= 5) {
            $tokensToRemove = array_slice($existingTokens, 5);
            foreach ($tokensToRemove as $token) {
                $this->em->remove($token);
            }
            $this->em->flush();
        }
    }

    /**
     * Generate a full SSO URL for a token string.
     */
    public function generateSsoUrl(string $tokenString): string
    {
        return $this->router->named(
            routeName: 'public:sso:login',
            absolute: true
        ) . '?token=' . urlencode($tokenString);
    }
}
