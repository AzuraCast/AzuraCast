<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170502202418 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings CHANGE setting_value setting_value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE backend_config backend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE nowplaying_data nowplaying_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE automation_settings automation_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings CHANGE setting_value setting_value LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE backend_config backend_config LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE nowplaying_data nowplaying_data LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE automation_settings automation_settings LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\'');
    }
}
