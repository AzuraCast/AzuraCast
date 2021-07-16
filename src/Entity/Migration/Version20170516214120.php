<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170516214120 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX update_idx ON listener');
        $this->addSql('CREATE INDEX update_idx ON listener (listener_hash)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX update_idx ON listener');
        $this->addSql('CREATE INDEX update_idx ON listener (listener_uid, listener_ip)');
    }
}
