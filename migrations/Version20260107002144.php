<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107002144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE item_recipe_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE item_recipe (id INT NOT NULL, ingredient_id INT NOT NULL, output_id INT NOT NULL, output_quantity INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5C71C4F8DE097880 ON item_recipe (output_id)');
        $this->addSql('CREATE INDEX idx_ingredient ON item_recipe (ingredient_id)');
        $this->addSql('ALTER TABLE item_recipe ADD CONSTRAINT FK_5C71C4F8933FE08C FOREIGN KEY (ingredient_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_recipe ADD CONSTRAINT FK_5C71C4F8DE097880 FOREIGN KEY (output_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE item_recipe_id_seq CASCADE');
        $this->addSql('ALTER TABLE item_recipe DROP CONSTRAINT FK_5C71C4F8933FE08C');
        $this->addSql('ALTER TABLE item_recipe DROP CONSTRAINT FK_5C71C4F8DE097880');
        $this->addSql('DROP TABLE item_recipe');
    }
}
