<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210528180443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correct many tables having incorrectly nullable relation tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_keys CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE podcast CHANGE storage_location_id storage_location_id INT NOT NULL');
        $this->addSql('ALTER TABLE podcast_category CHANGE podcast_id podcast_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE podcast_episode CHANGE podcast_id podcast_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE podcast_media CHANGE storage_location_id storage_location_id INT NOT NULL');
        $this->addSql('ALTER TABLE settings CHANGE app_unique_identifier app_unique_identifier CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE sftp_user CHANGE station_id station_id INT NOT NULL');
        $this->addSql('ALTER TABLE station_playlist_folders CHANGE station_id station_id INT NOT NULL, CHANGE playlist_id playlist_id INT NOT NULL');
        $this->addSql('ALTER TABLE station_streamer_broadcasts CHANGE station_id station_id INT NOT NULL, CHANGE streamer_id streamer_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_login_tokens CHANGE user_id user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_keys CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE podcast CHANGE storage_location_id storage_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE podcast_category CHANGE podcast_id podcast_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE podcast_episode CHANGE podcast_id podcast_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE podcast_media CHANGE storage_location_id storage_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE app_unique_identifier app_unique_identifier CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE sftp_user CHANGE station_id station_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_playlist_folders CHANGE station_id station_id INT DEFAULT NULL, CHANGE playlist_id playlist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_streamer_broadcasts CHANGE station_id station_id INT DEFAULT NULL, CHANGE streamer_id streamer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_login_tokens CHANGE user_id user_id INT DEFAULT NULL');
    }
}
