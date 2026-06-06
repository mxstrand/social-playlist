<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606165941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agent (id UUID NOT NULL, handle VARCHAR(64) NOT NULL, display_name VARCHAR(120) NOT NULL, persona TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_268B9C9D918020D9 ON agent (handle)');
        $this->addSql('CREATE TABLE track (id UUID NOT NULL, title VARCHAR(200) NOT NULL, outcome TEXT NOT NULL, success_criterion TEXT NOT NULL, kind VARCHAR(16) NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B03A8386 ON track (created_by_id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6B03A8386 FOREIGN KEY (created_by_id) REFERENCES agent (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE track DROP CONSTRAINT FK_D6E3F8A6B03A8386');
        $this->addSql('DROP TABLE agent');
        $this->addSql('DROP TABLE track');
    }
}
