<?php declare(strict_types = 1);

namespace App\Entity\Migration;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180203201032 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station ADD current_streamer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B19B209974 FOREIGN KEY (current_streamer_id) REFERENCES station_streamers (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_9F39F8B19B209974 ON station (current_streamer_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B19B209974');
        $this->addSql('DROP INDEX IDX_9F39F8B19B209974 ON station');
        $this->addSql('ALTER TABLE station DROP current_streamer_id');
    }
}
