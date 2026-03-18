<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260318130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional related_book_id to article table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD related_book_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66A342D4135 FOREIGN KEY (related_book_id) REFERENCES book (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_23A0E66A342D4135 ON article (related_book_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66A342D4135');
        $this->addSql('DROP INDEX IDX_23A0E66A342D4135 ON article');
        $this->addSql('ALTER TABLE article DROP related_book_id');
    }
}
