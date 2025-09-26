<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class VersionS20250924082940 extends AbstractMigration
{
     public function getDescription(): string
    {
        return 'Add repost to post and reply system';
    }

    public function up(Schema $schema): void
    {
        // Ajout colonne anonymous a la table forum
        $this->addSql('ALTER TABLE forum ADD anonymous BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE forum ADD debussy_clairDeLune BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Suppression colonne anonymous a la table forum
        $this->addSql('ALTER TABLE forum DROP anonymous');
        $this->addSql('ALTER TABLE forum DROP debussy_clairDeLune');
    }
}
