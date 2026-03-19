<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour supprimer tous les forums "café des lumières"
 * et leurs posts/commentaires associés
 */
final class VersionS20260309120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime tous les forums café des lumières et leur contenu associé';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les likes de commentaires des posts des forums café des lumières
        $this->addSql("
            DELETE ul FROM user_like ul
            INNER JOIN comment c ON ul.comment_id = c.id
            INNER JOIN post p ON c.post_id = p.id
            INNER JOIN forum f ON p.forum_id = f.id
            WHERE f.special = 'cafe_des_lumieres'
        ");

        // Supprimer les commentaires des posts des forums café des lumières
        $this->addSql("
            DELETE c FROM comment c
            INNER JOIN post p ON c.post_id = p.id
            INNER JOIN forum f ON p.forum_id = f.id
            WHERE f.special = 'cafe_des_lumieres'
        ");

        // Supprimer les likes des posts des forums café des lumières
        $this->addSql("
            DELETE pl FROM post_likes pl
            INNER JOIN post p ON pl.post_id = p.id
            INNER JOIN forum f ON p.forum_id = f.id
            WHERE f.special = 'cafe_des_lumieres'
        ");

        // Supprimer tous les posts des forums café des lumières
        $this->addSql("
            DELETE p FROM post p
            INNER JOIN forum f ON p.forum_id = f.id
            WHERE f.special = 'cafe_des_lumieres'
        ");

        // Supprimer les forums café des lumières
        $this->addSql("DELETE FROM forum WHERE special = 'cafe_des_lumieres'");
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback possible car les données sont supprimées
        $this->addSql('-- Migration irreversible');
    }
}
