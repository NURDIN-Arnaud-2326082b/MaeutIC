<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103AddNetworkColumn extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add JSON "network" column to user to store list of user ids in the network';
    }

    public function up(Schema $schema): void
    {
        // Add a JSON column (stored as LONGTEXT with Doctrine JSON mapping comment for portability)
        $this->addSql("ALTER TABLE `user` ADD network LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` DROP COLUMN IF EXISTS network");
    }
}
