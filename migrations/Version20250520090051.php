<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250520090051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE private_conversation (id INT AUTO_INCREMENT NOT NULL, user1_id INT DEFAULT NULL, user2_id INT DEFAULT NULL, INDEX IDX_DCF38EEB56AE248B (user1_id), INDEX IDX_DCF38EEB441B8B65 (user2_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE private_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, conversation_id INT NOT NULL, sender_id INT DEFAULT NULL, INDEX IDX_4744FC9B9AC0396 (conversation_id), INDEX IDX_4744FC9BF624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE private_conversation ADD CONSTRAINT FK_DCF38EEB56AE248B FOREIGN KEY (user1_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE private_conversation ADD CONSTRAINT FK_DCF38EEB441B8B65 FOREIGN KEY (user2_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE private_message ADD CONSTRAINT FK_4744FC9B9AC0396 FOREIGN KEY (conversation_id) REFERENCES private_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE private_message ADD CONSTRAINT FK_4744FC9BF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE private_conversation DROP FOREIGN KEY FK_DCF38EEB56AE248B');
        $this->addSql('ALTER TABLE private_conversation DROP FOREIGN KEY FK_DCF38EEB441B8B65');
        $this->addSql('ALTER TABLE private_message DROP FOREIGN KEY FK_4744FC9B9AC0396');
        $this->addSql('ALTER TABLE private_message DROP FOREIGN KEY FK_4744FC9BF624B39D');
        $this->addSql('DROP TABLE private_conversation');
        $this->addSql('DROP TABLE private_message');
    }
}
