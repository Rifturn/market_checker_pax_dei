<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109203108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE avatar_teleport_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE avatar_teleport (id INT NOT NULL, avatar_id INT NOT NULL, map VARCHAR(50) NOT NULL, zone VARCHAR(100) NOT NULL, unlocked BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_avatar_teleport ON avatar_teleport (avatar_id)');
        $this->addSql('CREATE INDEX idx_map_zone ON avatar_teleport (map, zone)');
        $this->addSql('CREATE UNIQUE INDEX unique_avatar_location ON avatar_teleport (avatar_id, map, zone)');
        $this->addSql('COMMENT ON COLUMN avatar_teleport.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN avatar_teleport.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE avatar_teleport ADD CONSTRAINT FK_B93A075186383B10 FOREIGN KEY (avatar_id) REFERENCES avatar (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE avatar_teleport_id_seq CASCADE');
        $this->addSql('ALTER TABLE avatar_teleport DROP CONSTRAINT FK_B93A075186383B10');
        $this->addSql('DROP TABLE avatar_teleport');
    }
}
