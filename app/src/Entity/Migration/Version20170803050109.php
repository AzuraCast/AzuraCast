<?php

namespace Entity\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170803050109 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Empty migration accidentally committed.
        $this->addSql('SELECT 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Empty migration accidentally committed.
        $this->addSql('SELECT 1');
    }
}
