<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

/**
 * Add a separate per-IP request cooldown, distinct from the request playability delay.
 */
final class Version20260920150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add request_delay_ip column to Station entity, separate from request_delay.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD request_delay_ip INT DEFAULT NULL');
    }

    public function postup(Schema $schema): void
    {
        // Preserve existing behavior for stations that already have requests enabled:
        // carry over their current request_delay value as the new per-IP cooldown,
        // so the effective anti-flood threshold does not silently change.
        $this->addSql(
            <<<'SQL'
                UPDATE station
                SET request_delay_ip = request_delay
                WHERE enable_requests = 1
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP request_delay_ip');
    }
}
