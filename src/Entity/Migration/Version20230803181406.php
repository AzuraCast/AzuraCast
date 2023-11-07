<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

#[
    StableMigration('0.19.1'),
    StableMigration('0.19.0')
]
final class Version20230803181406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand length of Station "genre" field.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station CHANGE genre genre VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station CHANGE genre genre VARCHAR(150) DEFAULT NULL');
    }
}
