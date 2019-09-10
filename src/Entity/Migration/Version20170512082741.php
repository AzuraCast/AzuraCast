<?php
namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170512082741 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE song_history ADD request_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history ADD CONSTRAINT FK_2AD16164427EB8A5 FOREIGN KEY (request_id) REFERENCES station_requests (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2AD16164427EB8A5 ON song_history (request_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE song_history DROP FOREIGN KEY FK_2AD16164427EB8A5');
        $this->addSql('DROP INDEX UNIQ_2AD16164427EB8A5 ON song_history');
        $this->addSql('ALTER TABLE song_history DROP request_id');
    }
}
