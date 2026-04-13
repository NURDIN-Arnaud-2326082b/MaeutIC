<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260413120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sensitive content flags to posts for admin review';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD has_sensitive_content TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE post ADD sensitive_content_warnings JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP COLUMN sensitive_content_warnings');
        $this->addSql('ALTER TABLE post DROP COLUMN has_sensitive_content');
    }
}
