<?php declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180412055024 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station_playlists ADD source VARCHAR(50) NOT NULL, ADD include_in_requests TINYINT(1) NOT NULL');
    }

    public function postUp(Schema $schema)
    {
        $this->connection->update('station_playlists', [
            'include_in_requests' => 1,
        ], ['type' => 'default']);

        $this->connection->update('station_playlists', [
            'source' => 'default',
        ], [1 => 1]);
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE station_playlists DROP source, DROP include_in_requests');
    }
}
