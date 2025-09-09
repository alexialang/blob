-- Script pour ajouter le type de question "Vrai/Faux" 
-- À exécuter si les fixtures ne sont pas rechargées

-- Vérifier si le type existe déjà
SELECT * FROM type_question WHERE name = 'true_false';

-- Ajouter le type s'il n'existe pas
INSERT IGNORE INTO type_question (name) VALUES ('true_false');

-- Vérifier que l'insertion a fonctionné
SELECT id, name FROM type_question ORDER BY id;
