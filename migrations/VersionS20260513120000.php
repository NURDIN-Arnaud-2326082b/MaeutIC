<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260513120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused author bio columns: bio_type and bio_article_id (cleanup)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author DROP FOREIGN KEY FK_BDAACEADC7B4365D');
        $this->addSql('DROP INDEX IDX_BDAACEADC7B4365D ON author');
        $this->addSql('ALTER TABLE author DROP bio_type, DROP bio_article_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author ADD bio_type VARCHAR(50) DEFAULT NULL, ADD bio_article_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE author ADD CONSTRAINT FK_BDAACEADC7B4365D FOREIGN KEY (bio_article_id) REFERENCES article (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BDAACEADC7B4365D ON author (bio_article_id)');
    }
}
