<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250820203859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room_player DROP FOREIGN KEY FK_D957BCA454177093');
        $this->addSql('ALTER TABLE room_player ADD CONSTRAINT FK_D957BCA454177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room_player DROP FOREIGN KEY FK_D957BCA454177093');
        $this->addSql('ALTER TABLE room_player ADD CONSTRAINT FK_D957BCA454177093 FOREIGN KEY (room_id) REFERENCES room (id)');
    }
}
