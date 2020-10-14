<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190324040155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_remotes ADD display_name VARCHAR(255) DEFAULT NULL, ADD is_visible_on_public_pages TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE station_mounts ADD display_name VARCHAR(255) DEFAULT NULL, ADD is_visible_on_public_pages TINYINT(1) NOT NULL');

        $this->addSql('UPDATE station_remotes SET is_visible_on_public_pages=1');
        $this->addSql('UPDATE station_mounts SET is_visible_on_public_pages=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_mounts DROP display_name, DROP is_visible_on_public_pages');
        $this->addSql('ALTER TABLE station_remotes DROP display_name, DROP is_visible_on_public_pages');
    }
}
