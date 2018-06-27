<?php declare(strict_types = 1);

namespace Entity\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Based to Version20180506022642
 */
class Version20180608130900 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

	$this->addSql('ALTER TABLE station_mounts ADD custom_listenurl VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

	$this->addSql('ALTER TABLE station_mounts DROP ustom_listenurl');
    }
}
