<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Enums\LoginTokenTypes;
use App\Entity\User;
use App\Entity\UserLoginToken;
use App\Security\SplitToken;

/**
 * @extends AbstractSplitTokenRepository<UserLoginToken>
 */
final class UserLoginTokenRepository extends AbstractSplitTokenRepository
{
    protected string $entityClass = UserLoginToken::class;

    /**
     * @return array{
     *     SplitToken,
     *     UserLoginToken
     * }
     */
    public function createToken(
        User $user,
        ?LoginTokenTypes $type,
        ?string $comment = null,
        int $expiresMinutes = 30,
    ): array {
        $token = SplitToken::generate();

        $loginToken = new UserLoginToken(
            $user,
            $token,
            $type ?? LoginTokenTypes::default(),
            $comment,
            $expiresMinutes
        );
        $this->em->persist($loginToken);
        $this->em->flush();

        return [
            $token,
            $loginToken,
        ];
    }

    public function revokeForUser(User $user): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\UserLoginToken ult
                WHERE ult.user = :user
            DQL
        )->setParameter('user', $user)
            ->execute();
    }

    public function cleanup(): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\UserLoginToken ut 
                WHERE ut.expires_at <= :time
            DQL
        )->setParameter('time', time())
            ->execute();
    }
}
