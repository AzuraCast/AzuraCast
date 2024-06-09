<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240609154223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add option to disable Liquidsoap playlist definition generation if not needed';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD enable_liquidsoap_playlist_definitions TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP enable_liquidsoap_playlist_definitions');
    }

}
