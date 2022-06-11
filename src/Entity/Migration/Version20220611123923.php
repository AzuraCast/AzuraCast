<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220611123923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add current_song relation to Station.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD current_song_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE station ADD CONSTRAINT FK_9F39F8B1AB03776 FOREIGN KEY (current_song_id) REFERENCES song_history (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_9F39F8B1AB03776 ON station (current_song_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B1AB03776');
        $this->addSql('DROP INDEX IDX_9F39F8B1AB03776 ON station');
        $this->addSql('ALTER TABLE station DROP current_song_id');
    }
}
