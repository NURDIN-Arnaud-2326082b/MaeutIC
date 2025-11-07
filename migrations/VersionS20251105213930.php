<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20251105213930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout d un attribut "genre" dans la table "user"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD genre VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP genre');
    }

}
