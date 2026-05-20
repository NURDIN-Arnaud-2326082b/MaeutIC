<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionS20260512153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split author name into first_name and last_name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL');

        $this->addSql("UPDATE author SET
            last_name = CASE
                WHEN name IS NULL OR TRIM(name) = '' THEN ''
                WHEN INSTR(TRIM(name), ' ') = 0 THEN TRIM(name)
                ELSE SUBSTRING_INDEX(TRIM(name), ' ', -1)
            END,
            first_name = CASE
                WHEN name IS NULL OR TRIM(name) = '' THEN ''
                WHEN INSTR(TRIM(name), ' ') = 0 THEN TRIM(name)
                ELSE TRIM(SUBSTRING(TRIM(name), 1, LENGTH(TRIM(name)) - LENGTH(SUBSTRING_INDEX(TRIM(name), ' ', -1))))
            END");

        $this->addSql('ALTER TABLE author MODIFY first_name VARCHAR(255) NOT NULL, MODIFY last_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE author DROP name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author ADD name VARCHAR(255) NOT NULL');
        $this->addSql("UPDATE author SET name = TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))");
        $this->addSql('ALTER TABLE author DROP first_name, DROP last_name');
    }
}
