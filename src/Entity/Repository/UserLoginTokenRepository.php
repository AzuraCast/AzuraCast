<?php

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

    public function cleanup(): void
    {
        $threshold = time()-86400; // One day

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\UserLoginToken ut WHERE ut.created_at <= :threshold
            DQL
        )->setParameter('threshold', $threshold)
            ->execute();
    }

}
