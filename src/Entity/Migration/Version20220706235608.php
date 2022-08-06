<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220706235608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "is_visible" denormalization to song_history table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE song_history ADD is_visible TINYINT(1) NOT NULL');
        $this->addSql('CREATE INDEX idx_is_visible ON song_history (is_visible)');

        $this->addSql('UPDATE song_history SET is_visible=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_is_visible ON song_history');
        $this->addSql('ALTER TABLE song_history DROP is_visible');
    }
}
