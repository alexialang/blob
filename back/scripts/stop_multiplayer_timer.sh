#!/bin/bash

# Script pour arrêter le timer multijoueur

# Aller dans le répertoire du projet
cd "$(dirname "$0")/.."

# Lire le PID
if [ -f var/run/multiplayer-timer.pid ]; then
    PID=$(cat var/run/multiplayer-timer.pid)
    
    # Vérifier si le processus existe
    if ps -p $PID > /dev/null; then
        echo "Arrêt du timer multijoueur (PID: $PID)..."
        kill $PID
        
        # Attendre que le processus se termine
        sleep 2
        
        if ps -p $PID > /dev/null; then
            echo "Force killing timer (PID: $PID)..."
            kill -9 $PID
        fi
        
        echo "Timer arrêté"
    else
        echo "Aucun processus timer trouvé avec le PID $PID"
    fi
    
    # Nettoyer le fichier PID
    rm -f var/run/multiplayer-timer.pid
else
    echo "Aucun fichier PID trouvé. Le timer n'est peut-être pas lancé."
fi

