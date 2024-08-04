<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.20.2')]
final class Version20240710235245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uploaded_at field to station media.';
    }

    public function preUp(Schema $schema): void
    {
        $this->connection->executeQuery(
            <<<'SQL'
                DELETE FROM unprocessable_media WHERE mtime IS NULL
            SQL
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD uploaded_at INT NOT NULL AFTER mtime');
        $this->addSql('ALTER TABLE unprocessable_media CHANGE mtime mtime INT NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeQuery(
            <<<'SQL'
                UPDATE station_media SET uploaded_at=IF(mtime = 0, UNIX_TIMESTAMP(), mtime)
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP uploaded_at');
        $this->addSql('ALTER TABLE unprocessable_media CHANGE mtime mtime INT DEFAULT NULL');
    }
}
