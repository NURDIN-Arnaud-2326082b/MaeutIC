<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260506140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manual biography text to authors';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author ADD bio_content LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author DROP bio_content');
    }
}
