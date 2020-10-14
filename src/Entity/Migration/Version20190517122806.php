<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190517122806 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Mitigate an issue affecting some playlists.
        $this->addSql('UPDATE station_playlists SET remote_type="stream" WHERE remote_type IS NULL OR remote_type NOT IN ("playlist", "stream")');
    }

    public function down(Schema $schema): void
    {
    }
}
