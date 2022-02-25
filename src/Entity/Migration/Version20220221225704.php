<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221225704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indices to station_queue to optimize performance.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_played_status ON station_queue (is_played, timestamp_played)');
        $this->addSql('CREATE INDEX idx_cued_status ON station_queue (sent_to_autodj, timestamp_cued)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_played_status ON station_queue');
        $this->addSql('DROP INDEX idx_cued_status ON station_queue');
    }
}
