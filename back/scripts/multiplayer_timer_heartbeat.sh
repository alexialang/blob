#!/bin/bash

# Script de heartbeat pour le timer multijoueur
# Envoie des mises à jour toutes les 2 secondes pour une meilleure précision

API_BASE_URL="http://localhost:8000/api"
GAME_ID=""

# Fonction d'aide
show_help() {
    echo "Usage: $0 <game_id>"
    echo "  game_id: L'ID du jeu multijoueur"
    echo ""
    echo "Exemple: $0 game_abc123"
}

# Vérifier les arguments
if [ $# -eq 0 ]; then
    show_help
    exit 1
fi

GAME_ID="$1"

echo "🚀 Démarrage du heartbeat pour le jeu: $GAME_ID"
echo "📡 Envoi de mises à jour toutes les 2 secondes..."
echo "⏹️  Appuyez sur Ctrl+C pour arrêter"
echo ""

# Boucle principale
while true; do
    # Envoyer la mise à jour du timer
    response=$(curl -s -X POST "$API_BASE_URL/multiplayer/game/$GAME_ID/timer-update" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE" \
        2>/dev/null)
    
    if [ $? -eq 0 ]; then
        echo "✅ Timer update envoyé à $(date '+%H:%M:%S')"
    else
        echo "❌ Erreur envoi timer update à $(date '+%H:%M:%S')"
    fi
    
    # Attendre 2 secondes pour une meilleure précision
    sleep 2
done
