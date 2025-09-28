<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250928014152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE reading_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE report_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE reading (id INT NOT NULL, user_id INT NOT NULL, value DOUBLE PRECISION NOT NULL, note VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_fasting BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C11AFC41A76ED395 ON reading (user_id)');
        $this->addSql('CREATE TABLE report (id INT NOT NULL, user_id INT NOT NULL, date DATE NOT NULL, filename VARCHAR(255) NOT NULL, type VARCHAR(20) DEFAULT \'daily\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C42F7784A76ED395 ON report (user_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE reading ADD CONSTRAINT FK_C11AFC41A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE reading_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE report_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE reading DROP CONSTRAINT FK_C11AFC41A76ED395');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784A76ED395');
        $this->addSql('DROP TABLE reading');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE "user"');
    }
}
