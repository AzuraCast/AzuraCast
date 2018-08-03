<?php declare(strict_types = 1);

namespace App\Entity\Migration;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20171214104226 extends AbstractMigration
{
    public function preUp(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Deleting duplicate user accounts to avoid constraint errors in subsequent update
        $users = $this->connection->fetchAll("SELECT * FROM users ORDER BY uid ASC");
        $emails = [];

        foreach($users as $row) {
            $email = $row['email'];
            if (isset($emails[$email])) {
                $this->connection->delete('users', ['uid' => $row['uid']]);
            } else {
                $emails[$email] = $email;
            }
        }
    }

    public function up(Schema $schema)
    {
        $this->addSql('CREATE UNIQUE INDEX email_idx ON users (email)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX email_idx ON users');
    }
}
