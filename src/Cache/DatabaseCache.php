<?php

declare(strict_types=1);

namespace App\Cache;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

final class DatabaseCache extends DoctrineDbalAdapter
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        parent::__construct($connection);
        $this->setLogger($logger);
    }
}
