<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20240702170603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds station bitrate limit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD max_bitrate INT DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP max_bitrate');
    }
}
