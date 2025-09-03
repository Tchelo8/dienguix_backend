<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826145528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE echange_rate (id SERIAL NOT NULL, from_currency VARCHAR(255) NOT NULL, to_currency VARCHAR(255) NOT NULL, rate NUMERIC(15, 8) NOT NULL, source VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, status BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE operator (id SERIAL NOT NULL, country_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, logo VARCHAR(255) NOT NULL, min_amount NUMERIC(12, 2) NOT NULL, max_amount NUMERIC(12, 2) NOT NULL, fees_structure INT DEFAULT NULL, is_active BOOLEAN NOT NULL, status BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D7A6A781F92F3E70 ON operator (country_id)');
        $this->addSql('COMMENT ON COLUMN operator.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE operator ADD CONSTRAINT FK_D7A6A781F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE operator DROP CONSTRAINT FK_D7A6A781F92F3E70');
        $this->addSql('DROP TABLE echange_rate');
        $this->addSql('DROP TABLE operator');
    }
}
