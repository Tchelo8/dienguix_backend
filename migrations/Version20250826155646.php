<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826155646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transaction (id SERIAL NOT NULL, sender_id INT DEFAULT NULL, receiver_id INT DEFAULT NULL, from_country_id INT DEFAULT NULL, to_country_id INT DEFAULT NULL, exchange_rate_id INT DEFAULT NULL, operator_sender_id INT DEFAULT NULL, operator_receiver_id INT DEFAULT NULL, amount_sent NUMERIC(10, 2) NOT NULL, trans_fees NUMERIC(10, 2) DEFAULT NULL, amount_received NUMERIC(10, 2) NOT NULL, amount_send_code VARCHAR(255) NOT NULL, amount_received_code VARCHAR(255) NOT NULL, status BOOLEAN NOT NULL, trans_status VARCHAR(255) NOT NULL, transaction_type VARCHAR(255) NOT NULL, payment_method VARCHAR(255) NOT NULL, note VARCHAR(1000) DEFAULT NULL, transaction_ref VARCHAR(255) NOT NULL, iniated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, failed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D1F624B39D ON transaction (sender_id)');
        $this->addSql('CREATE INDEX IDX_723705D1CD53EDB6 ON transaction (receiver_id)');
        $this->addSql('CREATE INDEX IDX_723705D1D28BF877 ON transaction (from_country_id)');
        $this->addSql('CREATE INDEX IDX_723705D1DE1CDC0D ON transaction (to_country_id)');
        $this->addSql('CREATE INDEX IDX_723705D1FB53491E ON transaction (exchange_rate_id)');
        $this->addSql('CREATE INDEX IDX_723705D130FC4C5A ON transaction (operator_sender_id)');
        $this->addSql('CREATE INDEX IDX_723705D164EA856A ON transaction (operator_receiver_id)');
        $this->addSql('COMMENT ON COLUMN transaction.iniated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN transaction.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN transaction.failed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1F624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1D28BF877 FOREIGN KEY (from_country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1DE1CDC0D FOREIGN KEY (to_country_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1FB53491E FOREIGN KEY (exchange_rate_id) REFERENCES echange_rate (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D130FC4C5A FOREIGN KEY (operator_sender_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D164EA856A FOREIGN KEY (operator_receiver_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1F624B39D');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1CD53EDB6');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1D28BF877');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1DE1CDC0D');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1FB53491E');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D130FC4C5A');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D164EA856A');
        $this->addSql('DROP TABLE transaction');
    }
}
