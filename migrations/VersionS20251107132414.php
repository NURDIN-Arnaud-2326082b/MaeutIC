<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20251107132414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename content to encrypted_content in message table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message CHANGE content encrypted_content LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE message CHANTE encrypted_content content LONGTEXT NOT NULL');
    }
}
