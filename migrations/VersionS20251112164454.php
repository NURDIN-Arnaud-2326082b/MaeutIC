<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20251112164454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add JSON "blockedby" column to user to store list of user ids who have blocked this user';
    }

    public function up(Schema $schema): void
    {
        // add blockedby column as JSON (LONGTEXT with DC2Type:json)
        $this->addSql("ALTER TABLE `user` ADD blockedby LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` DROP COLUMN IF EXISTS blockedby");
    }
}
