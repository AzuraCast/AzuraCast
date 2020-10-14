<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171124184831 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP art');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media ADD art LONGBLOB DEFAULT NULL');
    }
}
