<?php declare(strict_types=1);

namespace AppMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow an event to have no venue yet, so its venue can be decided by vote';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event CHANGE location_id location_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE event SET location_id = (SELECT MIN(id) FROM location) WHERE location_id IS NULL');
        $this->addSql('ALTER TABLE event CHANGE location_id location_id INT NOT NULL');
    }
}
