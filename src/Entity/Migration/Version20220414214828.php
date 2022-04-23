<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220414214828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE storage_location ADD sftp_host VARCHAR(255) DEFAULT NULL, ADD sftp_username VARCHAR(255) DEFAULT NULL, ADD sftp_password VARCHAR(255) DEFAULT NULL, ADD sftp_port INT DEFAULT NULL, ADD sftp_private_key LONGTEXT DEFAULT NULL, ADD sftp_private_key_pass_phrase VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE storage_location DROP sftp_host, DROP sftp_username, DROP sftp_password, DROP sftp_port, DROP sftp_private_key, DROP sftp_private_key_pass_phrase');
    }
}
