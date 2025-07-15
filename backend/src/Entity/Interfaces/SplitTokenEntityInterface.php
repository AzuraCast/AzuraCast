<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Entity\User;
use App\Security\SplitToken;

interface SplitTokenEntityInterface
{
    public function verify(SplitToken $userSuppliedToken): bool;

    public function getUser(): User;
}
