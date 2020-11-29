<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180203201032 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD current_streamer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B19B209974 FOREIGN KEY (current_streamer_id) REFERENCES station_streamers (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_9F39F8B19B209974 ON station (current_streamer_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B19B209974');
        $this->addSql('DROP INDEX IDX_9F39F8B19B209974 ON station');
        $this->addSql('ALTER TABLE station DROP current_streamer_id');
    }
}
