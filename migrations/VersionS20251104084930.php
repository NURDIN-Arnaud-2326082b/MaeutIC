<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20251104084930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création d un attribut "special" non null dans la table "forum", valeur par défaut "general"';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum ADD special VARCHAR(255) DEFAULT "general"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forum DROP special');
    }

}
