<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811115631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_admin');
        $this->addSql('ALTER TABLE user_permission CHANGE permission permission VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_permission CHANGE permission permission VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE user ADD is_admin TINYINT(1) NOT NULL');
    }
}
