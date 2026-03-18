<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260318142000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy author column from article table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP author');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD author VARCHAR(255) NOT NULL');
    }
}
