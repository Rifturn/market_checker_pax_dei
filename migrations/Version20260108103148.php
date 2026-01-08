<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108103148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE avatar_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE avatar_skill_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE skill_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE avatar (id INT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1677722FA76ED395 ON avatar (user_id)');
        $this->addSql('COMMENT ON COLUMN avatar.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN avatar.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE avatar_skill (id INT NOT NULL, avatar_id INT NOT NULL, skill_id INT NOT NULL, level INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4FAE4B4086383B10 ON avatar_skill (avatar_id)');
        $this->addSql('CREATE INDEX IDX_4FAE4B405585C142 ON avatar_skill (skill_id)');
        $this->addSql('CREATE TABLE skill (id INT NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, ui_group VARCHAR(50) NOT NULL, skill_level_cap INT NOT NULL, skill_base_xp INT NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_type_name VARCHAR(100) NOT NULL, listing_path VARCHAR(255) NOT NULL, category_ids JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E3DE4779F75D7B0 ON skill (external_id)');
        $this->addSql('ALTER TABLE avatar ADD CONSTRAINT FK_1677722FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE avatar_skill ADD CONSTRAINT FK_4FAE4B4086383B10 FOREIGN KEY (avatar_id) REFERENCES avatar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE avatar_skill ADD CONSTRAINT FK_4FAE4B405585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE avatar_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE avatar_skill_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE skill_id_seq CASCADE');
        $this->addSql('ALTER TABLE avatar DROP CONSTRAINT FK_1677722FA76ED395');
        $this->addSql('ALTER TABLE avatar_skill DROP CONSTRAINT FK_4FAE4B4086383B10');
        $this->addSql('ALTER TABLE avatar_skill DROP CONSTRAINT FK_4FAE4B405585C142');
        $this->addSql('DROP TABLE avatar');
        $this->addSql('DROP TABLE avatar_skill');
        $this->addSql('DROP TABLE skill');
    }
}
