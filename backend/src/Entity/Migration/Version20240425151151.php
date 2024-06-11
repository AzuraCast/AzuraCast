<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;

#[
    StableMigration('0.19.6'),
    StableMigration('0.19.7')
]
final class Version20240425151151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_enabled flag for podcasts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast ADD is_enabled TINYINT(1) NOT NULL AFTER description');

        $this->addSql(<<<'SQL'
            UPDATE podcast
            SET is_enabled=1
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast DROP is_enabled');
    }
}
