<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171214104226 extends AbstractMigration
{
    public function preup(Schema $schema): void
    {
        // Deleting duplicate user accounts to avoid constraint errors in subsequent update
        $users = $this->connection->fetchAllAssociative('SELECT * FROM users ORDER BY uid ASC');
        $emails = [];

        foreach ($users as $row) {
            $email = $row['email'];
            if (isset($emails[$email])) {
                $this->connection->delete('users', ['uid' => $row['uid']]);
            } else {
                $emails[$email] = $email;
            }
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX email_idx ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX email_idx ON users');
    }
}
