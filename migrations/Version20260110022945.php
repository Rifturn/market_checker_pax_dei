<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110022945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE notification_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notification_reaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE notification (id INT NOT NULL, avatar_id INT NOT NULL, type VARCHAR(20) NOT NULL, message TEXT NOT NULL, metadata JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BF5476CA86383B10 ON notification (avatar_id)');
        $this->addSql('CREATE INDEX idx_notification_created ON notification (created_at)');
        $this->addSql('COMMENT ON COLUMN notification.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE notification_reaction (id INT NOT NULL, notification_id INT NOT NULL, user_id INT NOT NULL, emoji VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F1C73F52EF1A9D84 ON notification_reaction (notification_id)');
        $this->addSql('CREATE INDEX IDX_F1C73F52A76ED395 ON notification_reaction (user_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_notification_emoji ON notification_reaction (user_id, notification_id, emoji)');
        $this->addSql('COMMENT ON COLUMN notification_reaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA86383B10 FOREIGN KEY (avatar_id) REFERENCES avatar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_reaction ADD CONSTRAINT FK_F1C73F52EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_reaction ADD CONSTRAINT FK_F1C73F52A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE notification_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notification_reaction_id_seq CASCADE');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA86383B10');
        $this->addSql('ALTER TABLE notification_reaction DROP CONSTRAINT FK_F1C73F52EF1A9D84');
        $this->addSql('ALTER TABLE notification_reaction DROP CONSTRAINT FK_F1C73F52A76ED395');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE notification_reaction');
    }
}
