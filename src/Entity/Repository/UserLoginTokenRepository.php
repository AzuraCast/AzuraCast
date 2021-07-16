<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity;
use App\Security\SplitToken;

class UserLoginTokenRepository extends AbstractSplitTokenRepository
{
    public function createToken(Entity\User $user): SplitToken
    {
        $token = SplitToken::generate();

        $loginToken = new Entity\UserLoginToken($user, $token);
        $this->em->persist($loginToken);
        $this->em->flush();

        return $token;
    }

    public function revokeForUser(Entity\User $user): void
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
        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        $threshold = time() - 86400; // One day

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\UserLoginToken ut WHERE ut.created_at <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }
}
