<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240319115513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make Podcast Episode publish date always have a value.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                UPDATE podcast_episode
                SET publish_at = created_at
                WHERE publish_at IS NULL
            SQL
        );

        $this->addSql('ALTER TABLE podcast_episode CHANGE publish_at publish_at INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast_episode CHANGE publish_at publish_at INT DEFAULT NULL');
    }
}
