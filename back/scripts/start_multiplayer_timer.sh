#!/bin/bash

# Script pour démarrer le timer multijoueur en arrière-plan

# Aller dans le répertoire du projet
cd "$(dirname "$0")/.."

# Lancer la commande timer en arrière-plan
echo "Démarrage du timer multijoueur..."
nohup php bin/console app:multiplayer-timer > var/log/multiplayer-timer.log 2>&1 &

# Sauvegarder le PID
echo $! > var/run/multiplayer-timer.pid

echo "Timer multijoueur démarré (PID: $!)"
echo "Logs disponibles dans var/log/multiplayer-timer.log"

