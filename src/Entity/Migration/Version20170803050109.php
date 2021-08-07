<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20170803050109 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Empty migration accidentally committed.
        $this->addSql('-- "Ignore Migration"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // Empty migration accidentally committed.
        $this->addSql('-- "Ignore Migration"');
    }
}
