<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20251106121145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add JSON "blocked" column to user to store list of blocked user ids';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` ADD blocked LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` DROP COLUMN IF EXISTS blocked");
    }
}
