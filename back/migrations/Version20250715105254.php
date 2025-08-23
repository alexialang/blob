<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250715105254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quiz_group (quiz_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_91B33D45853CD175 (quiz_id), INDEX IDX_91B33D45FE54D947 (group_id), PRIMARY KEY(quiz_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quiz_group ADD CONSTRAINT FK_91B33D45853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quiz_group ADD CONSTRAINT FK_91B33D45FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_group DROP FOREIGN KEY FK_91B33D45853CD175');
        $this->addSql('ALTER TABLE quiz_group DROP FOREIGN KEY FK_91B33D45FE54D947');
        $this->addSql('DROP TABLE quiz_group');
    }
}
