<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241124233431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added blocks for the cms';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cms_block (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, language VARCHAR(2) NOT NULL, type INT NOT NULL, json JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_AD680C0EC4663E4 (page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cms_block ADD CONSTRAINT FK_AD680C0EC4663E4 FOREIGN KEY (page_id) REFERENCES cms (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cms_block DROP FOREIGN KEY FK_AD680C0EC4663E4');
        $this->addSql('DROP TABLE cms_block');
    }
}
