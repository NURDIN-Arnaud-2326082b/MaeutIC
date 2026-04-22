<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260421120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add secure download token fields to data_access_request';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE data_access_request ADD download_token_hash VARCHAR(64) DEFAULT NULL, ADD download_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE data_access_request DROP download_token_hash, DROP download_token_expires_at');
    }
}
