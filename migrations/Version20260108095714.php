<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108095714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE spell_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE spell_item (id INT NOT NULL, spell_id INT NOT NULL, item_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2160704F479EC90D ON spell_item (spell_id)');
        $this->addSql('CREATE INDEX IDX_2160704F126F525E ON spell_item (item_id)');
        $this->addSql('ALTER TABLE spell_item ADD CONSTRAINT FK_2160704F479EC90D FOREIGN KEY (spell_id) REFERENCES spell (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE spell_item ADD CONSTRAINT FK_2160704F126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE spell ADD description TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE spell_item_id_seq CASCADE');
        $this->addSql('ALTER TABLE spell_item DROP CONSTRAINT FK_2160704F479EC90D');
        $this->addSql('ALTER TABLE spell_item DROP CONSTRAINT FK_2160704F126F525E');
        $this->addSql('DROP TABLE spell_item');
        $this->addSql('ALTER TABLE spell DROP description');
    }
}
