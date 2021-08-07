<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20161007195027 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE role_permissions (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, station_id INT DEFAULT NULL, action_name VARCHAR(50) NOT NULL, INDEX IDX_1FBA94E6D60322AC (role_id), INDEX IDX_1FBA94E621BDB235 (station_id), UNIQUE INDEX role_permission_unique_idx (role_id, action_name, station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E621BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO role_permissions (role_id, action_name, station_id) SELECT rha.role_id, a.name, rha.station_id FROM role_has_actions AS rha INNER JOIN action AS a ON rha.action_id = a.id');

        $this->addSql('ALTER TABLE role_has_actions DROP FOREIGN KEY FK_50EEC1BD9D32F035');
        $this->addSql('DROP TABLE action');
        $this->addSql('DROP TABLE role_has_actions');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) DEFAULT NULL COLLATE utf8_unicode_ci, is_global TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_has_actions (id INT AUTO_INCREMENT NOT NULL, station_id INT DEFAULT NULL, action_id INT NOT NULL, role_id INT NOT NULL, UNIQUE INDEX role_action_unique_idx (role_id, action_id, station_id), INDEX IDX_50EEC1BDD60322AC (role_id), INDEX IDX_50EEC1BD21BDB235 (station_id), INDEX IDX_50EEC1BD9D32F035 (action_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BD21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BD9D32F035 FOREIGN KEY (action_id) REFERENCES action (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BDD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE role_permissions');
    }
}
