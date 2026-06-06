<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606171106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback (id UUID NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, track_id UUID NOT NULL, by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D22944585ED23C43 ON feedback (track_id)');
        $this->addSql('CREATE INDEX IDX_D2294458AAE72004 ON feedback (by_id)');
        $this->addSql('CREATE TABLE performance (id UUID NOT NULL, model VARCHAR(80) NOT NULL, succeeded BOOLEAN NOT NULL, note VARCHAR(280) NOT NULL, excerpt TEXT DEFAULT NULL, evidence_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, track_id UUID NOT NULL, by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_82D796815ED23C43 ON performance (track_id)');
        $this->addSql('CREATE INDEX IDX_82D79681AAE72004 ON performance (by_id)');
        $this->addSql('CREATE TABLE vote (id UUID NOT NULL, value INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, track_id UUID NOT NULL, by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5A1085645ED23C43 ON vote (track_id)');
        $this->addSql('CREATE INDEX IDX_5A108564AAE72004 ON vote (by_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_vote_agent_track ON vote (by_id, track_id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D22944585ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458AAE72004 FOREIGN KEY (by_id) REFERENCES agent (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE performance ADD CONSTRAINT FK_82D796815ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE performance ADD CONSTRAINT FK_82D79681AAE72004 FOREIGN KEY (by_id) REFERENCES agent (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085645ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564AAE72004 FOREIGN KEY (by_id) REFERENCES agent (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE track ADD score INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP CONSTRAINT FK_D22944585ED23C43');
        $this->addSql('ALTER TABLE feedback DROP CONSTRAINT FK_D2294458AAE72004');
        $this->addSql('ALTER TABLE performance DROP CONSTRAINT FK_82D796815ED23C43');
        $this->addSql('ALTER TABLE performance DROP CONSTRAINT FK_82D79681AAE72004');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT FK_5A1085645ED23C43');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT FK_5A108564AAE72004');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE performance');
        $this->addSql('DROP TABLE vote');
        $this->addSql('ALTER TABLE track DROP score');
    }
}
