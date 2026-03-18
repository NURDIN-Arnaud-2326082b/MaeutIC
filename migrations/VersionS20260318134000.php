<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260318134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional related_author_id to article table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD related_author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66AF675F31B FOREIGN KEY (related_author_id) REFERENCES author (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_23A0E66AF675F31B ON article (related_author_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66AF675F31B');
        $this->addSql('DROP INDEX IDX_23A0E66AF675F31B ON article');
        $this->addSql('ALTER TABLE article DROP related_author_id');
    }
}
