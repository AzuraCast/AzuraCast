<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170719045113 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history ADD sent_to_autodj TINYINT(1) NOT NULL');
    }

    public function postup(Schema $schema): void
    {
        $this->connection->executeStatement(
            'UPDATE song_history SET sent_to_autodj=1 WHERE timestamp_cued != 0 AND timestamp_cued IS NOT NULL'
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history DROP sent_to_autodj');
    }
}
