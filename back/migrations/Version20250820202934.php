<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250820202934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B853CD175');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B853CD175');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
    }
}
