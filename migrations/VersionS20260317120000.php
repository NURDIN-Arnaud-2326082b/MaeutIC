<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260317120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional image_path column to post table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP image_path');
    }
}
