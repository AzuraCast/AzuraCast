<?php declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180826043500 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->changeCharset('utf8mb4', 'utf8mb4_general_ci');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->changeCharset('utf8mb4', 'utf8mb4_unicode_ci');
    }

    protected function changeCharset($charset, $collate)
    {
        $db_name = $this->connection->getDatabase();

        $tables = ['analytics', 'api_keys', 'app_migrations', 'custom_field', 'listener', 'role', 'role_permissions', 'settings', 'song_history', 'songs', 'station', 'station_media', 'station_media_art', 'station_media_custom_field', 'station_mounts', 'station_playlist_media', 'station_playlists', 'station_requests', 'station_requests', 'station_streamers', 'station_webhooks', 'user_has_role', 'users'];

        $this->addSql([
            'ALTER DATABASE '.$this->connection->quoteIdentifier($db_name).' CHARACTER SET = '.$charset.' COLLATE = '.$collate,
            'ALTER TABLE `song_history` DROP FOREIGN KEY FK_2AD16164A0BDB2F3',
            'ALTER TABLE `station_media` DROP FOREIGN KEY FK_32AADE3AA0BDB2F3',
        ]);

        foreach($tables as $table_name) {
            $this->addSql('ALTER TABLE '.$this->connection->quoteIdentifier($table_name).' CONVERT TO CHARACTER SET '.$charset.' COLLATE '.$collate);
        }

        $this->addSql([
            'ALTER TABLE `song_history` ADD CONSTRAINT FK_2AD16164A0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE CASCADE',
            'ALTER TABLE `station_media` ADD CONSTRAINT FK_32AADE3AA0BDB2F3 FOREIGN KEY (song_id) REFERENCES songs (id) ON DELETE SET NULL',
        ]);
    }
}
