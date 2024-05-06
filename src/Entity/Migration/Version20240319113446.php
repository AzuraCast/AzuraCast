<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240319113446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove Doctrine type annotations from earlier DBAL versions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE analytics CHANGE moment moment DATETIME NOT NULL');
        $this->addSql('ALTER TABLE audit_log CHANGE changes changes JSON NOT NULL');
        $this->addSql('ALTER TABLE podcast CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE podcast_category CHANGE podcast_id podcast_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE podcast_episode DROP INDEX UNIQ_77EB2BD017421B18, ADD INDEX IDX_77EB2BD017421B18 (playlist_media_id)');
        $this->addSql('ALTER TABLE podcast_episode CHANGE id id CHAR(36) NOT NULL, CHANGE podcast_id podcast_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE podcast_media CHANGE id id CHAR(36) NOT NULL, CHANGE episode_id episode_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE app_unique_identifier app_unique_identifier CHAR(36) NOT NULL, CHANGE update_results update_results JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config JSON DEFAULT NULL, CHANGE backend_config backend_config JSON DEFAULT NULL, CHANGE branding_config branding_config JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE station_webhooks CHANGE triggers triggers JSON DEFAULT NULL, CHANGE config config JSON DEFAULT NULL, CHANGE metadata metadata JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE song_history CHANGE delta_points delta_points LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE podcast_media CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE episode_id episode_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE audit_log CHANGE changes changes LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE station_webhooks CHANGE triggers triggers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE config config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE metadata metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE settings CHANGE app_unique_identifier app_unique_identifier CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE update_results update_results LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE analytics CHANGE moment moment DATETIME NOT NULL COMMENT \'(DC2Type:carbon_immutable)\'');
        $this->addSql('ALTER TABLE podcast_category CHANGE podcast_id podcast_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE podcast_episode DROP INDEX IDX_77EB2BD017421B18, ADD UNIQUE INDEX UNIQ_77EB2BD017421B18 (playlist_media_id)');
        $this->addSql('ALTER TABLE podcast_episode CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE podcast_id podcast_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE station CHANGE frontend_config frontend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE backend_config backend_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE branding_config branding_config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }
}
