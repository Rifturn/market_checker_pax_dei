<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108093330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE spell_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE spell (id INT NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, icon_path VARCHAR(500) DEFAULT NULL, cooldown_duration DOUBLE PRECISION DEFAULT NULL, range DOUBLE PRECISION DEFAULT NULL, cost_attribute VARCHAR(100) DEFAULT NULL, cost_amount_min DOUBLE PRECISION DEFAULT NULL, entity_type VARCHAR(100) NOT NULL, entity_type_name VARCHAR(100) NOT NULL, listing_path VARCHAR(255) NOT NULL, category_ids JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D03FCD8D9F75D7B0 ON spell (external_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE spell_id_seq CASCADE');
        $this->addSql('DROP TABLE spell');
    }
}
