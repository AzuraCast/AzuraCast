<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210805004608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add author and e-mail to podcast table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast ADD author VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE podcast DROP author, DROP email');
    }
}
