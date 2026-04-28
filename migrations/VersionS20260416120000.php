<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260416120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create data_access_request table for RGPD data access workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE data_access_request (id INT AUTO_INCREMENT NOT NULL, requester_id INT NOT NULL, processed_by_id INT DEFAULT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', processed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', admin_note LONGTEXT DEFAULT NULL, INDEX idx_data_access_request_status_created (status, created_at), INDEX IDX_4310E8A6A3C47A27 (requester_id), INDEX IDX_4310E8A6862D8A42 (processed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE data_access_request ADD CONSTRAINT FK_4310E8A6A3C47A27 FOREIGN KEY (requester_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE data_access_request ADD CONSTRAINT FK_4310E8A6862D8A42 FOREIGN KEY (processed_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE data_access_request');
    }
}
