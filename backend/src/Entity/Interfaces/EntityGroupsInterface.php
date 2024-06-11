<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface EntityGroupsInterface
{
    public const string GROUP_ID = 'id';

    public const string GROUP_GENERAL = 'general';

    public const string GROUP_ADMIN = 'admin';

    public const string GROUP_ALL = 'all';
}
