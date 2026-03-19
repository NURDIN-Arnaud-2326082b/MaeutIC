<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class VersionS20260319102947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66a342d4135 TO IDX_23A0E6629BB7924');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66af675f31b TO IDX_23A0E665EB4AF00');
        $this->addSql('ALTER TABLE book ADD isbn VARCHAR(255) DEFAULT NULL, DROP link');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `FK_9474526C4B89032C`');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `FK_9474526CA76ED395`');
        $this->addSql('ALTER TABLE comment CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forum CHANGE special special VARCHAR(255) DEFAULT \'general\' NOT NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `FK_NOTIFICATION_RECIPIENT`');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `FK_NOTIFICATION_SENDER`');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_notification_recipient TO IDX_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_notification_sender TO IDX_BF5476CAF624B39D');
        $this->addSql('DROP INDEX IDX_USED ON password_reset_token');
        $this->addSql('DROP INDEX IDX_EXPIRES ON password_reset_token');
        $this->addSql('ALTER TABLE password_reset_token CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE used used TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE password_reset_token RENAME INDEX idx_user TO IDX_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY `FK_5A8A6C8D29CCBAD0`');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY `FK_5A8A6C8DA76ED395`');
        $this->addSql('ALTER TABLE post CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post RENAME INDEX idx_5a8a6c8d6de44026 TO IDX_5A8A6C8D39C1776A');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY `FK_232B5D404B89032C`');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY `FK_232B5D40A76ED395`');
        $this->addSql('ALTER TABLE post_likes CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_DED1C292A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_DED1C2924B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE post_likes RENAME INDEX idx_232b5d40a76ed395 TO IDX_DED1C292A76ED395');
        $this->addSql('ALTER TABLE post_likes RENAME INDEX idx_232b5d404b89032c TO IDX_DED1C2924B89032C');
        $this->addSql('ALTER TABLE user CHANGE network network JSON DEFAULT NULL, CHANGE blocked blocked JSON DEFAULT NULL, CHANGE blockedby blockedby JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e6629bb7924 TO IDX_23A0E66A342D4135');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e665eb4af00 TO IDX_23A0E66AF675F31B');
        $this->addSql('ALTER TABLE book ADD link VARCHAR(255) NOT NULL, DROP isbn');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `FK_9474526CA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `FK_9474526C4B89032C` FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum CHANGE special special VARCHAR(255) DEFAULT \'general\'');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF624B39D');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT `FK_NOTIFICATION_RECIPIENT` FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT `FK_NOTIFICATION_SENDER` FOREIGN KEY (sender_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_bf5476cae92f8f78 TO IDX_NOTIFICATION_RECIPIENT');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_bf5476caf624b39d TO IDX_NOTIFICATION_SENDER');
        $this->addSql('ALTER TABLE password_reset_token CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE used used TINYINT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_USED ON password_reset_token (used)');
        $this->addSql('CREATE INDEX IDX_EXPIRES ON password_reset_token (expires_at)');
        $this->addSql('ALTER TABLE password_reset_token RENAME INDEX idx_6b7ba4b6a76ed395 TO IDX_USER');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D29CCBAD0');
        $this->addSql('ALTER TABLE post CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT `FK_5A8A6C8DA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT `FK_5A8A6C8D29CCBAD0` FOREIGN KEY (forum_id) REFERENCES forum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post RENAME INDEX idx_5a8a6c8d39c1776a TO IDX_5A8A6C8D6DE44026');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY FK_DED1C292A76ED395');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY FK_DED1C2924B89032C');
        $this->addSql('ALTER TABLE post_likes CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT `FK_232B5D404B89032C` FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT `FK_232B5D40A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_likes RENAME INDEX idx_ded1c292a76ed395 TO IDX_232B5D40A76ED395');
        $this->addSql('ALTER TABLE post_likes RENAME INDEX idx_ded1c2924b89032c TO IDX_232B5D404B89032C');
        $this->addSql('ALTER TABLE user CHANGE network network LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE blocked blocked LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE blockedby blockedby LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }
}
