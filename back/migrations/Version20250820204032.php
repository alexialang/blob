<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250820204032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFB54177093');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFB54177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFB54177093');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFB54177093 FOREIGN KEY (room_id) REFERENCES room (id)');
    }
}
