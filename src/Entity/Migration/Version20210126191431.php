<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210126191431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mount/remote to listeners.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE listener ADD mount_id INT DEFAULT NULL, ADD remote_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE listener ADD CONSTRAINT FK_959C3422538228B8 FOREIGN KEY (mount_id) REFERENCES station_mounts (id) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE listener ADD CONSTRAINT FK_959C34222A3E9C94 FOREIGN KEY (remote_id) REFERENCES station_remotes (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX IDX_959C3422538228B8 ON listener (mount_id)');
        $this->addSql('CREATE INDEX IDX_959C34222A3E9C94 ON listener (remote_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE listener DROP FOREIGN KEY FK_959C3422538228B8');
        $this->addSql('ALTER TABLE listener DROP FOREIGN KEY FK_959C34222A3E9C94');
        $this->addSql('DROP INDEX IDX_959C3422538228B8 ON listener');
        $this->addSql('DROP INDEX IDX_959C34222A3E9C94 ON listener');
        $this->addSql('ALTER TABLE listener DROP mount_id, DROP remote_id');
    }
}
