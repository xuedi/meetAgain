<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241122161805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added basic cms table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cms (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(128) DEFAULT NULL, slug VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', published TINYINT(1) NOT NULL, INDEX IDX_AC8F9907B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cms ADD CONSTRAINT FK_AC8F9907B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cms DROP FOREIGN KEY FK_AC8F9907B03A8386');
        $this->addSql('DROP TABLE cms');
    }
}
