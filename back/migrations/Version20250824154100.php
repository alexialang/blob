<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la colonne shared_scores à la table game_session.
 */
final class Version20250824154100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne shared_scores à la table game_session pour stocker les scores partagés entre joueurs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_session ADD shared_scores JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_session DROP shared_scores');
    }
}
