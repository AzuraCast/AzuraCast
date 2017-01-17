<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161003041904 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE role_has_actions (id INT AUTO_INCREMENT NOT NULL, station_id INT DEFAULT NULL, role_id INT NOT NULL, action_id INT NOT NULL, INDEX IDX_50EEC1BDD60322AC (role_id), INDEX IDX_50EEC1BD21BDB235 (station_id), UNIQUE INDEX role_action_unique_idx (role_id, action_id, station_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE role_has_actions ADD CONSTRAINT FK_50EEC1BD21BDB235 FOREIGN KEY (station_id) REFERENCES station (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO role_has_actions (role_id, action_id) SELECT role_id, action_id FROM role_has_action');
        $this->addSql('DROP TABLE role_has_action');

        $this->addSql('ALTER TABLE action ADD is_global TINYINT(1) NOT NULL');
        $this->addSql('UPDATE action SET is_global=1');

        $actions = [
            ['view administration', 1],
            ['administer settings', 1],
            ['administer api keys', 1],
            ['administer user accounts', 1],
            ['administer permissions', 1],
            ['administer stations', 1],

            ['view station management', 0],
            ['view station reports', 0],
            ['manage station profile', 0],
            ['manage station broadcasting', 0],
            ['manage station streamers', 0],
            ['manage station media', 0],
            ['manage station automation', 0],
        ];

        foreach($actions as $action)
        {
            $this->addSql('DELETE FROM action WHERE name = :name', [
                'name' => $action[0],
            ], [
                'string' => 'string',
            ]);

            $this->addSql('INSERT INTO action (name, is_global) VALUES (:name, :is_global)', [
                'name' => $action[0],
                'is_global' => $action[1],
            ], [
                'string' => 'string',
                'int' => 'int',
            ]);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE role_has_action (role_id INT NOT NULL, action_id INT NOT NULL, INDEX IDX_E4DAF125D60322AC (role_id), INDEX IDX_E4DAF1259D32F035 (action_id), PRIMARY KEY(role_id, action_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE role_has_action ADD CONSTRAINT FK_E4DAF1259D32F035 FOREIGN KEY (action_id) REFERENCES action (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_has_action ADD CONSTRAINT FK_E4DAF125D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');

        $this->addSql('DELETE FROM action WHERE is_global = 0');
        $this->addSql('INSERT INTO role_has_action (role_id, action_id) SELECT role_id, action_id FROM role_has_actions');

        $this->addSql('DROP TABLE role_has_actions');
        $this->addSql('ALTER TABLE action DROP is_global');
    }
}
