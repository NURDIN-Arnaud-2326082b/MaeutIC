<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260430180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop obsolete author.link column (we now use bio_url)';
    }

    public function up(Schema $schema): void
    {
        // Drop the old `link` column from `author` if it exists.
        // Safe because production currently has no data in that column.
        $this->addSql('ALTER TABLE author DROP link');
    }

    public function down(Schema $schema): void
    {
        // Recreate the column (nullable) in case of rollback
        $this->addSql('ALTER TABLE author ADD link VARCHAR(255) DEFAULT NULL');
    }
}
