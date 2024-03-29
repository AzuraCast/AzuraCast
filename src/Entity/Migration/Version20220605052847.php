<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.17.0')]
final class Version20220605052847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add selectable automatic backup format.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings ADD backup_format VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings DROP backup_format');
    }
}
