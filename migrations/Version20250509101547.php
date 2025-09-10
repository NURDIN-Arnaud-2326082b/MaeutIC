<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509101547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE user ADD profile_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_like DROP FOREIGN KEY FK_D6E20C7AF8697D13');
        $this->addSql('ALTER TABLE user_like ADD CONSTRAINT FK_D6E20C7AF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user DROP profile_image');
        $this->addSql('ALTER TABLE user_like DROP FOREIGN KEY FK_D6E20C7AF8697D13');
        $this->addSql('ALTER TABLE user_like ADD CONSTRAINT FK_D6E20C7AF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
