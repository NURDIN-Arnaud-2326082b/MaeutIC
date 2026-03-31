<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Skip problematic migration and go straight to adding isBanned column
 */
final class VersionS20260331120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isBanned column for user banning functionality (skips problematic index migration)';
    }

    public function up(Schema $schema): void
    {
        // Check if the column already exists to avoid errors
        $table = $schema->getTable('user');
        if (!$table->hasColumn('is_banned')) {
            $this->addSql('ALTER TABLE `user` ADD is_banned TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN IF EXISTS is_banned');
    }
}
