<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20161006030903 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_manages_station');

        $this->addSql('DELETE FROM role_has_actions WHERE role_id NOT IN (SELECT id FROM role)');
        $this->addSql('DELETE FROM role_has_actions WHERE action_id NOT IN (SELECT id FROM action)');

        $this->addSql('ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BDD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BD9D32F035 FOREIGN KEY (action_id) REFERENCES action (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_50EEC1BD9D32F035 ON role_has_actions (action_id)');

        $this->addSql('ALTER TABLE users ADD timezone VARCHAR(100) DEFAULT NULL, ADD locale VARCHAR(25) DEFAULT NULL, ADD created_at INT NOT NULL, ADD updated_at INT NOT NULL, DROP auth_last_login_time, DROP auth_recovery_code, DROP gender, DROP customization');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_manages_station (user_id INT NOT NULL, station_id INT NOT NULL, INDEX IDX_2453B56BA76ED395 (user_id), INDEX IDX_2453B56B21BDB235 (station_id), PRIMARY KEY(user_id, station_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_manages_station ADD CONSTRAINT FK_2453B56B21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_manages_station ADD CONSTRAINT FK_2453B56BA76ED395 FOREIGN KEY (user_id) REFERENCES users (uid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_has_actions DROP FOREIGN KEY FK_50EEC1BDD60322AC');
        $this->addSql('ALTER TABLE role_has_actions DROP FOREIGN KEY FK_50EEC1BD9D32F035');
        $this->addSql('DROP INDEX IDX_50EEC1BD9D32F035 ON role_has_actions');
        $this->addSql('ALTER TABLE users ADD auth_last_login_time INT DEFAULT NULL, ADD auth_recovery_code VARCHAR(50) DEFAULT NULL COLLATE utf8_unicode_ci, ADD gender VARCHAR(1) DEFAULT NULL COLLATE utf8_unicode_ci, ADD customization LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json)\', DROP timezone, DROP locale, DROP created_at, DROP updated_at');
    }
}
