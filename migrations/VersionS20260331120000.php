<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add isBanned column to user table for admin ban/unban functionality
 */
final class VersionS20260331120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isBanned column to user table to track banned accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD is_banned TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN IF EXISTS is_banned');
    }
}
