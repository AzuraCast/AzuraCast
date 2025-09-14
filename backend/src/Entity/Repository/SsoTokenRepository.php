<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\SsoToken;
use App\Entity\User;
use App\Security\SplitToken;

/**
 * @extends AbstractSplitTokenRepository<SsoToken>
 */
final class SsoTokenRepository extends AbstractSplitTokenRepository
{
    protected string $entityClass = SsoToken::class;

    /**
     * Create a new SSO token for a user.
     */
    public function createToken(
        User $user,
        string $comment = '',
        int $expiresIn = 300,
        ?string $ipAddress = null,
        ?SplitToken $token = null
    ): SsoToken {
        if ($token === null) {
            $token = SplitToken::generate();
        }
        
        $ssoToken = new SsoToken(
            user: $user,
            token: $token,
            comment: $comment,
            expiresIn: $expiresIn,
            ipAddress: $ipAddress
        );

        $this->em->persist($ssoToken);
        $this->em->flush();

        return $ssoToken;
    }

    /**
     * Find and validate an SSO token.
     */
    public function findValidToken(string $tokenString): ?SsoToken
    {
        try {
            $userSuppliedToken = SplitToken::fromKeyString($tokenString);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $tokenEntity = $this->repository->find($userSuppliedToken->identifier);

        if (!$tokenEntity instanceof SsoToken) {
            return null;
        }

        // Verify the token
        if (!$tokenEntity->verify($userSuppliedToken)) {
            return null;
        }

        // Check if token is still valid (not used and not expired)
        if (!$tokenEntity->isValid()) {
            return null;
        }

        return $tokenEntity;
    }

    /**
     * Mark a token as used.
     */
    public function markTokenAsUsed(SsoToken $token): void
    {
        // Since SsoToken is readonly, we need to create a new entity with used=true
        // or use a different approach. For now, we'll delete the token after use.
        $this->em->remove($token);
        $this->em->flush();
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete(SsoToken::class, 't')
           ->where('t.expires_at < :now')
           ->setParameter('now', time());

        return $qb->getQuery()->execute();
    }

    /**
     * Get tokens for a specific user.
     *
     * @return SsoToken[]
     */
    public function getTokensForUser(User $user): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from($this->entityClass, 't')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get active (non-expired, non-used) tokens for a specific user.
     *
     * @return SsoToken[]
     */
    public function getActiveTokensForUser(User $user): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from($this->entityClass, 't')
            ->where('t.user = :user')
            ->andWhere('t.used = :used')
            ->andWhere('t.expires_at > :now')
            ->setParameter('user', $user)
            ->setParameter('used', false)
            ->setParameter('now', time())
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get count of expired tokens without deleting them.
     */
    public function getExpiredTokenCount(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from($this->entityClass, 't')
            ->where('t.expires_at <= :now')
            ->setParameter('now', time())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
