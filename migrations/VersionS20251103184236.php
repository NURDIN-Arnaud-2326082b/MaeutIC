<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table for network requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, sender_id INT DEFAULT NULL, type VARCHAR(100) NOT NULL, data JSON DEFAULT NULL, status VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, is_read TINYINT(1) NOT NULL, INDEX IDX_NOTIFICATION_RECIPIENT (recipient_id), INDEX IDX_NOTIFICATION_SENDER (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_RECIPIENT FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_SENDER FOREIGN KEY (sender_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_NOTIFICATION_RECIPIENT');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_NOTIFICATION_SENDER');
        $this->addSql('DROP TABLE notification');
    }
}
