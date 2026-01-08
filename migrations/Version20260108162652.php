<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108162652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE notified_listing_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE notified_listing (id INT NOT NULL, listing_id VARCHAR(255) NOT NULL, item_external_id VARCHAR(255) NOT NULL, zone VARCHAR(255) NOT NULL, price INT NOT NULL, quantity INT NOT NULL, notified_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_99B652E6D4619D1A ON notified_listing (listing_id)');
        $this->addSql('COMMENT ON COLUMN notified_listing.notified_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE notified_listing_id_seq CASCADE');
        $this->addSql('DROP TABLE notified_listing');
    }
}
