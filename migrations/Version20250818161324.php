<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250818161324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8D93D649D60322AC ON "user" (role_id)');
        $this->addSql('ALTER TABLE user_profile ADD document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_profile ADD document_number VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user_profile ADD document_file VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D95AB405C33F7837 ON user_profile (document_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649D60322AC');
        $this->addSql('DROP INDEX IDX_8D93D649D60322AC');
        $this->addSql('ALTER TABLE "user" DROP role_id');
        $this->addSql('ALTER TABLE user_profile DROP CONSTRAINT FK_D95AB405C33F7837');
        $this->addSql('DROP INDEX IDX_D95AB405C33F7837');
        $this->addSql('ALTER TABLE user_profile DROP document_id');
        $this->addSql('ALTER TABLE user_profile DROP document_number');
        $this->addSql('ALTER TABLE user_profile DROP document_file');
    }
}
