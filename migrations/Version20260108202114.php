<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108202114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE equipment_set_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE equipment_set (id INT NOT NULL, user_id INT NOT NULL, helmet_id INT DEFAULT NULL, gloves_id INT DEFAULT NULL, bracers_id INT DEFAULT NULL, chest_id INT DEFAULT NULL, legs_id INT DEFAULT NULL, boots_id INT DEFAULT NULL, main_hand_id INT DEFAULT NULL, off_hand_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D794EDBDA76ED395 ON equipment_set (user_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD4D4A700C ON equipment_set (helmet_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD4892748A ON equipment_set (gloves_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD75739F23 ON equipment_set (bracers_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD180955AC ON equipment_set (chest_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD42DBDF0B ON equipment_set (legs_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD9F68AC73 ON equipment_set (boots_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBD19901DB0 ON equipment_set (main_hand_id)');
        $this->addSql('CREATE INDEX IDX_D794EDBDA79AD546 ON equipment_set (off_hand_id)');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBDA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD4D4A700C FOREIGN KEY (helmet_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD4892748A FOREIGN KEY (gloves_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD75739F23 FOREIGN KEY (bracers_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD180955AC FOREIGN KEY (chest_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD42DBDF0B FOREIGN KEY (legs_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD9F68AC73 FOREIGN KEY (boots_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBD19901DB0 FOREIGN KEY (main_hand_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE equipment_set ADD CONSTRAINT FK_D794EDBDA79AD546 FOREIGN KEY (off_hand_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE equipment_set_id_seq CASCADE');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBDA76ED395');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD4D4A700C');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD4892748A');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD75739F23');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD180955AC');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD42DBDF0B');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD9F68AC73');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBD19901DB0');
        $this->addSql('ALTER TABLE equipment_set DROP CONSTRAINT FK_D794EDBDA79AD546');
        $this->addSql('DROP TABLE equipment_set');
    }
}
