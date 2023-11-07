<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use App\Entity\Attributes\StableMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

#[StableMigration('0.17.7')]
final class Version20230102192652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Station branding config column, part 2.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station DROP default_album_art_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station ADD default_album_art_url VARCHAR(255) DEFAULT NULL');
    }
}
