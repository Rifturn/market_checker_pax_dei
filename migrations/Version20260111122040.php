<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111122040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE notification_read_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE notification_read (id INT NOT NULL, user_id INT NOT NULL, notification_id INT NOT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_206B0A5DA76ED395 ON notification_read (user_id)');
        $this->addSql('CREATE INDEX IDX_206B0A5DEF1A9D84 ON notification_read (notification_id)');
        $this->addSql('CREATE UNIQUE INDEX user_notification_unique ON notification_read (user_id, notification_id)');
        $this->addSql('COMMENT ON COLUMN notification_read.read_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notification_read ADD CONSTRAINT FK_206B0A5DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_read ADD CONSTRAINT FK_206B0A5DEF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE notification_read_id_seq CASCADE');
        $this->addSql('ALTER TABLE notification_read DROP CONSTRAINT FK_206B0A5DA76ED395');
        $this->addSql('ALTER TABLE notification_read DROP CONSTRAINT FK_206B0A5DEF1A9D84');
        $this->addSql('DROP TABLE notification_read');
    }
}
