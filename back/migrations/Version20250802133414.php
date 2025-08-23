<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802133414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quiz_rating (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, quiz_id INT NOT NULL, rating INT NOT NULL, rated_at DATETIME NOT NULL, INDEX IDX_35CDD67BA76ED395 (user_id), INDEX IDX_35CDD67B853CD175 (quiz_id), UNIQUE INDEX unique_user_quiz_rating (user_id, quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quiz_rating ADD CONSTRAINT FK_35CDD67BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quiz_rating ADD CONSTRAINT FK_35CDD67B853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE user_answer DROP rating');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_rating DROP FOREIGN KEY FK_35CDD67BA76ED395');
        $this->addSql('ALTER TABLE quiz_rating DROP FOREIGN KEY FK_35CDD67B853CD175');
        $this->addSql('DROP TABLE quiz_rating');
        $this->addSql('ALTER TABLE user_answer ADD rating INT DEFAULT NULL');
    }
}
