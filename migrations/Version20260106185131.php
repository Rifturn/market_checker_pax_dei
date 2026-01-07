<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106185131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_view (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_847CE747A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_847CE747A76ED395 ON user_view (user_id)');
        $this->addSql('CREATE TABLE user_view_category (user_view_id INTEGER NOT NULL, category_id INTEGER NOT NULL, PRIMARY KEY(user_view_id, category_id), CONSTRAINT FK_B583F2865B1806CD FOREIGN KEY (user_view_id) REFERENCES user_view (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B583F28612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B583F2865B1806CD ON user_view_category (user_view_id)');
        $this->addSql('CREATE INDEX IDX_B583F28612469DE2 ON user_view_category (category_id)');
        $this->addSql('CREATE TABLE user_view_item (user_view_id INTEGER NOT NULL, item_entity_id INTEGER NOT NULL, PRIMARY KEY(user_view_id, item_entity_id), CONSTRAINT FK_E14164A65B1806CD FOREIGN KEY (user_view_id) REFERENCES user_view (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E14164A68DFF6BAD FOREIGN KEY (item_entity_id) REFERENCES item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E14164A65B1806CD ON user_view_item (user_view_id)');
        $this->addSql('CREATE INDEX IDX_E14164A68DFF6BAD ON user_view_item (item_entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_view');
        $this->addSql('DROP TABLE user_view_category');
        $this->addSql('DROP TABLE user_view_item');
    }
}
