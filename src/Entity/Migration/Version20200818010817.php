<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200818010817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch to Doctrine 2 JSON type; add log column to StationQueue.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings CHANGE setting_value setting_value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE backend_config backend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE automation_settings automation_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE station_queue ADD IF NOT EXISTS log LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE station_id station_id INT NOT NULL');
        $this->addSql('ALTER TABLE station_webhooks CHANGE triggers triggers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE config config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE metadata metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settings CHANGE setting_value setting_value LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\', CHANGE backend_config backend_config LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\', CHANGE automation_settings automation_settings LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE station_queue DROP log, CHANGE station_id station_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE station_webhooks CHANGE triggers triggers LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\', CHANGE config config LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\', CHANGE metadata metadata LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'(DC2Type:json_array)\'');
    }
}
