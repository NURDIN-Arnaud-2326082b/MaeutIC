<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionRemoveOldMessageEntities extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime les anciennes tables Chat, PrivateMessage et PrivateConversation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS chat');
        $this->addSql('DROP TABLE IF EXISTS private_message');
        $this->addSql('DROP TABLE IF EXISTS private_conversation');
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback automatique
    }
}
