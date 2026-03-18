<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260318112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add internal article content, image and pdf fields; make article link nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD content LONGTEXT DEFAULT NULL, ADD image_path VARCHAR(255) DEFAULT NULL, ADD pdf_path VARCHAR(255) DEFAULT NULL, CHANGE link link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP content, DROP image_path, DROP pdf_path, CHANGE link link VARCHAR(255) NOT NULL');
    }
}
