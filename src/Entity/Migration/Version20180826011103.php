<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update the index on song_history.
 */
final class Version20180826011103 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX sort_idx ON song_history');
        $this->addSql('CREATE INDEX history_idx ON song_history (timestamp_start, timestamp_end, listeners_start)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX history_idx ON song_history');
        $this->addSql('CREATE INDEX sort_idx ON song_history (timestamp_start)');
    }
}
