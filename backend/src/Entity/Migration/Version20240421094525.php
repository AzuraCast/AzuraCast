<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240421094525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand podcast database fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast ADD branding_config JSON DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE podcast_episode ADD season_number INT DEFAULT NULL, ADD episode_number INT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast DROP branding_config');
        $this->addSql('ALTER TABLE podcast_episode DROP season_number, DROP episode_number');
    }
}
