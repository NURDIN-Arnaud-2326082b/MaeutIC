<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922142104 extends AbstractMigration
{
     public function getDescription(): string
    {
        return 'Add repost to post and reply system';
    }

    public function up(Schema $schema): void
    {
        // Table article ############################################################
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66A76ED395');
        $this->addSql('ALTER TABLE article MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        // ##########################################################################

        // Table author #############################################################
        $this->addSql('ALTER TABLE author DROP FOREIGN KEY FK_BDAFD8C8A76ED395');
        $this->addSql('ALTER TABLE author MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE author ADD CONSTRAINT FK_BDAFD8C8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        // ##########################################################################

        // Table book ###############################################################
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331A76ED395');
        $this->addSql('ALTER TABLE book MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        // ##########################################################################

        // Table comment ############################################################
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        // ##########################################################################

        // Table conversation #########################################################
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E956AE248B');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9441B8B65');
        $this->addSql('ALTER TABLE conversation MODIFY user1_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation MODIFY user2_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E956AE248B FOREIGN KEY (user1_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9441B8B65 FOREIGN KEY (user2_id) REFERENCES user (id) ON DELETE SET NULL');
        // ##########################################################################

        // Table message ##############################################################
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message MODIFY sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE SET NULL');
        // ##########################################################################

        // Table post ###############################################################
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE post MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D29CCBAD0');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forum (id) ON DELETE CASCADE');
        
        // Ajout du système de réponses aux posts - avec vérification d'existence
        $this->addSql('ALTER TABLE post ADD COLUMN IF NOT EXISTS parent_post_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD COLUMN IF NOT EXISTS is_reply TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D6DE44026 FOREIGN KEY IF NOT EXISTS (parent_post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_5A8A6C8D6DE44026 ON post (parent_post_id)');
        // ##########################################################################

        // Table resource ###########################################################
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416A76ED395');
        $this->addSql('ALTER TABLE resource MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        // ##########################################################################

        // Table user_like ##########################################################
        $this->addSql('ALTER TABLE user_like DROP FOREIGN KEY FK_D6E20C7AA76ED395');
        $this->addSql('ALTER TABLE user_like ADD CONSTRAINT FK_D6E20C7AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_like DROP FOREIGN KEY FK_D6E20C7AF8697D13');
        $this->addSql('ALTER TABLE user_like ADD CONSTRAINT FK_D6E20C7AF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        // ##########################################################################

        // Table user_questions #####################################################
        $this->addSql('ALTER TABLE user_questions DROP FOREIGN KEY FK_8A3CD931A76ED395');
        $this->addSql('ALTER TABLE user_questions ADD CONSTRAINT FK_8A3CD931A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        // ##########################################################################
        
        // Table post_likes ##########################################################
        // Ajouter les contraintes seulement si elles n'existent pas
        $this->addSql('SET foreign_key_checks = 0');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY IF EXISTS FK_232B5D40A76ED395');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY IF EXISTS FK_232B5D404B89032C');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_232B5D40A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_likes ADD CONSTRAINT FK_232B5D404B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('SET foreign_key_checks = 1');
        // ##########################################################################
    }

    public function down(Schema $schema): void
    {
        // Table article ############################################################
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66A76ED395');
        $this->addSql('ALTER TABLE article MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // ##########################################################################

        // Table author #############################################################
        $this->addSql('ALTER TABLE author DROP FOREIGN KEY FK_BDAFD8C8A76ED395');
        $this->addSql('ALTER TABLE author MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE author ADD CONSTRAINT FK_BDAFD8C8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // ##########################################################################

        // Table book ###############################################################
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331A76ED395');
        $this->addSql('ALTER TABLE book MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // ##########################################################################

        // Table comment ############################################################
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        // ##########################################################################

        // Table conversation #########################################################
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E956AE248B');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9441B8B65');
        $this->addSql('ALTER TABLE conversation MODIFY user1_id INT NOT NULL');
        $this->addSql('ALTER TABLE conversation MODIFY user2_id INT NOT NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E956AE248B FOREIGN KEY (user1_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9441B8B65 FOREIGN KEY (user2_id) REFERENCES user (id)');
        // ##########################################################################

        // Table message ##############################################################
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message MODIFY sender_id INT NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)');
        // ##########################################################################

        // Table post ###############################################################
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE post MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D29CCBAD0');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D29CCBAD0 FOREIGN KEY (forum_id) REFERENCES post (id)');
        
        // Suppression du système de réponses aux posts
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY IF EXISTS FK_5A8A6C8D6DE44026');
        $this->addSql('DROP INDEX IF EXISTS IDX_5A8A6C8D6DE44026 ON post');
        $this->addSql('ALTER TABLE post DROP COLUMN IF EXISTS parent_post_id');
        $this->addSql('ALTER TABLE post DROP COLUMN IF EXISTS is_reply');
        // ##########################################################################

        // Table resource ###########################################################
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416A76ED395');
        $this->addSql('ALTER TABLE resource MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // ##########################################################################

        // Table user_like ##########################################################
        $this->addSql('ALTER TABLE user_like DROP FOREIGN KEY FK_D6E20C7AA76ED395');
        $this->addSql('ALTER TABLE user_like ADD CONSTRAINT FK_D6E20C7AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_like DROP FOREIGN KEY FK_D6E20C7AF8697D13');
        $this->addSql('ALTER TABLE user_like ADD CONSTRAINT FK_D6E20C7AF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        // ##########################################################################

        // Table user_questions #####################################################
        $this->addSql('ALTER TABLE user_questions DROP FOREIGN KEY FK_8A3CD931A76ED395');
        $this->addSql('ALTER TABLE user_questions ADD CONSTRAINT FK_8A3CD931A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // ##########################################################################

        // Table post_likes ##########################################################
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY IF EXISTS FK_232B5D40A76ED395');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY IF EXISTS FK_232B5D404B89032C');
        // ##########################################################################
    }
}
