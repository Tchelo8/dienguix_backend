<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908125253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_activity (id SERIAL NOT NULL, uzer_id INT DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, action VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, details VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C227C2858061825D ON log_activity (uzer_id)');
        $this->addSql('ALTER TABLE log_activity ADD CONSTRAINT FK_C227C2858061825D FOREIGN KEY (uzer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE log_activity DROP CONSTRAINT FK_C227C2858061825D');
        $this->addSql('DROP TABLE log_activity');
    }
}
