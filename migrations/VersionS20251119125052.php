<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20251119125052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create password_reset_token table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(100) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            used TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_USER (user_id),
            INDEX IDX_EXPIRES (expires_at),
            INDEX IDX_USED (used),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('DROP TABLE password_reset_token');
    }
}