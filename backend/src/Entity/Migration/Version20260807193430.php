<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20260807193430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add filter_local_user_urls global setting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD filter_local_user_urls TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP filter_local_user_urls');
    }
}
