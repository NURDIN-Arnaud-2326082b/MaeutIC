<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create report table for moderation reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, reporter_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, target_type VARCHAR(20) NOT NULL, target_id INT NOT NULL, reason VARCHAR(120) NOT NULL, details LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL DEFAULT \'pending\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', admin_note LONGTEXT DEFAULT NULL, INDEX idx_report_status_created (status, created_at), INDEX idx_report_target (target_type, target_id), INDEX IDX_C42E5A4D8626A8B3 (reporter_id), INDEX IDX_C42E5A4D7E0C2D49 (reviewed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42E5A4D8626A8B3 FOREIGN KEY (reporter_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42E5A4D7E0C2D49 FOREIGN KEY (reviewed_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE report');
    }
}
