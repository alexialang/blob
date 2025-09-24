#!/bin/bash

# Script pour charger les utilisateurs de test dans la base de données
# Tous les utilisateurs auront le mot de passe: Azert1!

echo "🚀 Chargement des utilisateurs de test..."

# Vérifier si le fichier .env existe
if [ ! -f .env ]; then
    echo "❌ Fichier .env non trouvé. Assurez-vous d'être dans le répertoire back/"
    exit 1
fi

# Charger les variables d'environnement
source .env

# Extraire les informations de connexion depuis DATABASE_URL
# Format: mysql://user:password@host:port/database
if [[ $DATABASE_URL =~ mysql://([^:]+):([^@]+)@([^:]+):([^/]+)/(.+) ]]; then
    DB_USER="${BASH_REMATCH[1]}"
    DB_PASS="${BASH_REMATCH[2]}"
    DB_HOST="${BASH_REMATCH[3]}"
    DB_PORT="${BASH_REMATCH[4]}"
    DB_NAME="${BASH_REMATCH[5]}"
else
    echo "❌ Impossible de parser DATABASE_URL: $DATABASE_URL"
    exit 1
fi

echo "📊 Connexion à la base de données: $DB_NAME sur $DB_HOST:$DB_PORT"

# Exécuter le script SQL
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < scripts/insert_users.sql

if [ $? -eq 0 ]; then
    echo "✅ Utilisateurs créés avec succès !"
    echo ""
    echo "👥 Comptes disponibles :"
    echo "📧 admin@test.com (Admin) - Mot de passe: Azert1!"
    echo "📧 user@test.com (User) - Mot de passe: Azert1!"
    echo "📧 alice@test.com (Alice Martin) - Mot de passe: Azert1!"
    echo "📧 bob@test.com (Bob Dupont) - Mot de passe: Azert1!"
    echo "... et 23 autres utilisateurs"
    echo ""
    echo "🎯 Total : 27 utilisateurs avec comptes validés"
    echo "🔑 Tous les mots de passe : Azert1!"
    echo ""
    echo "🎉 Vous pouvez maintenant tester la pagination !"
else
    echo "❌ Erreur lors de l'exécution du script SQL"
    exit 1
fi
