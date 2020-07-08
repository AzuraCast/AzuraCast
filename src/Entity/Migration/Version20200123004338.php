<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200123004338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand size of SFTP username field to 32 characters.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sftp_user CHANGE username username VARCHAR(32) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sftp_user CHANGE username username VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`');
    }
}
