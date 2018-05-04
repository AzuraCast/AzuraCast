<?php

namespace Entity\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170502202418 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE settings CHANGE setting_value setting_value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE backend_config backend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE nowplaying_data nowplaying_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', CHANGE automation_settings automation_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE settings CHANGE setting_value setting_value LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE backend_config backend_config LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE nowplaying_data nowplaying_data LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE automation_settings automation_settings LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\'');
    }
}
