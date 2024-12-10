<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210163155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added a new status to the user if the user is known and verified';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD verified TINYINT(1) NOT NULL, CHANGE name name VARCHAR(64) DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP verified, CHANGE name name VARCHAR(16) DEFAULT false');
    }
}
