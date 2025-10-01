<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[StableMigration('0.23.0')]
#[StableMigration('0.23.1')]
final class Version20250918041326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add podcast-global explicit flag.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast ADD explicit TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast DROP explicit');
    }
}
