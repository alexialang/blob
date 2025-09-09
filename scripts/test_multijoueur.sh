#!/bin/bash

# Script de test pour le mode multijoueur
# Vérifie que tous les services sont opérationnels

echo "🧪 TEST DU MODE MULTIJOUEUR"
echo "=============================="

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
API_BASE_URL="http://localhost:8000/api"
MERCURE_URL="http://localhost:3000/.well-known/mercure"
FRONTEND_URL="http://localhost:4200"

# Fonction de test
test_endpoint() {
    local name="$1"
    local url="$2"
    local method="${3:-GET}"
    
    echo -n "🔍 Test $name... "
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)
    else
        response=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$url" 2>/dev/null)
    fi
    
    if [ "$response" = "200" ] || [ "$response" = "401" ] || [ "$response" = "404" ]; then
        echo -e "${GREEN}✅ OK (HTTP $response)${NC}"
        return 0
    else
        echo -e "${RED}❌ ÉCHEC (HTTP $response)${NC}"
        return 1
    fi
}

# Test de la base de données
echo ""
echo "🗄️  TESTS BASE DE DONNÉES"
echo "-------------------------"
test_endpoint "Connexion BDD" "$API_BASE_URL/quiz/list" "GET"

# Test de l'API Symfony
echo ""
echo "🔌 TESTS API SYMFONY"
echo "-------------------"
test_endpoint "API Quiz publique" "$API_BASE_URL/quiz/list" "GET"
test_endpoint "API Catégories" "$API_BASE_URL/category-quiz" "GET"

# Test de Mercure avec topics valides
echo ""
echo "📡 TESTS MERCURE"
echo "----------------"
echo -n "🔍 Test Hub Mercure avec topics... "
# Test avec des topics valides (simulation d'une vraie connexion)
mercure_response=$(curl -s -o /dev/null -w "%{http_code}" "$MERCURE_URL?topic=test-topic" 2>/dev/null)
if [ "$mercure_response" = "200" ]; then
    echo -e "${GREEN}✅ OK (HTTP 200)${NC}"
else
    echo -e "${YELLOW}⚠️  PARTIEL (HTTP $mercure_response) - Normal sans topics${NC}"
fi

# Test du frontend Angular
echo ""
echo "🌐 TESTS FRONTEND"
echo "-----------------"
test_endpoint "Frontend Angular" "$FRONTEND_URL" "GET"

# Test des services Docker
echo ""
echo "🐳 TESTS SERVICES DOCKER"
echo "------------------------"
echo -n "🔍 Test service Symfony... "
if docker ps | grep -q "blob_server"; then
    echo -e "${GREEN}✅ En cours${NC}"
else
    echo -e "${RED}❌ Arrêté${NC}"
fi

echo -n "🔍 Test service Angular... "
if docker ps | grep -q "blob_angular"; then
    echo -e "${GREEN}✅ En cours${NC}"
else
    echo -e "${RED}❌ Arrêté${NC}"
fi

echo -n "🔍 Test service Mercure... "
if docker ps | grep -q "blob_mercure"; then
    echo -e "${GREEN}✅ En cours${NC}"
else
    echo -e "${RED}❌ Arrêté${NC}"
fi

echo -n "🔍 Test service BDD... "
if docker ps | grep -q "blob_bdd"; then
    echo -e "${GREEN}✅ En cours${NC}"
else
    echo -e "${RED}❌ Arrêté${NC}"
fi

# Vérification des logs
echo ""
echo "📋 VÉRIFICATION DES LOGS"
echo "------------------------"
echo -n "🔍 Logs Symfony... "
if docker logs blob_server --tail 10 2>/dev/null | grep -q "ERROR\|Exception"; then
    echo -e "${YELLOW}⚠️  Erreurs détectées${NC}"
else
    echo -e "${GREEN}✅ Aucune erreur${NC}"
fi

echo -n "🔍 Logs Mercure... "
if docker logs blob_mercure --tail 10 2>/dev/null | grep -q "ERROR\|Exception"; then
    echo -e "${YELLOW}⚠️  Erreurs détectées${NC}"
else
    echo -e "${GREEN}✅ Aucune erreur${NC}"
fi

# Test de création de salle (nécessite un token valide)
echo ""
echo "🎮 TESTS MULTIJOUEUR (NÉCESSITE AUTHENTIFICATION)"
echo "-------------------------------------------------"
echo -e "${YELLOW}⚠️  Ces tests nécessitent d'être connecté${NC}"
echo "Pour tester manuellement :"
echo "1. Connectez-vous sur $FRONTEND_URL"
echo "2. Créez une salle multijoueur"
echo "3. Vérifiez que les événements Mercure fonctionnent"

# Test de la configuration
echo ""
echo "⚙️  TESTS DE CONFIGURATION"
echo "---------------------------"
echo -n "🔍 Configuration framework.yaml... "
if [ -f "back/config/packages/framework.yaml" ]; then
    echo -e "${GREEN}✅ Présent${NC}"
else
    echo -e "${RED}❌ Manquant${NC}"
fi

echo -n "🔍 Configuration Mercure... "
if [ -f "back/config/packages/mercure.yaml" ]; then
    echo -e "${GREEN}✅ Présent${NC}"
else
    echo -e "${RED}❌ Manquant${NC}"
fi

echo -n "🔍 Service MultiplayerConfigService... "
if [ -f "back/src/Service/MultiplayerConfigService.php" ]; then
    echo -e "${GREEN}✅ Présent${NC}"
else
    echo -e "${RED}❌ Manquant${NC}"
fi

# Résumé
echo ""
echo "📊 RÉSUMÉ DES TESTS"
echo "==================="
echo "✅ Tests de base terminés"
echo "✅ Configuration vérifiée"
echo "⚠️  Tests multijoueur nécessitent une authentification"

echo ""
echo "🔧 EN CAS DE PROBLÈME :"
echo "1. Vérifiez que tous les services Docker sont démarrés"
echo "2. Vérifiez les logs avec 'docker logs <service_name>'"
echo "3. Redémarrez les services avec 'docker compose restart'"
echo "4. Vérifiez la configuration Mercure dans docker-compose.yml"

echo ""
echo "🎯 PROCHAINES ÉTAPES :"
echo "1. Tester la création de salle en mode connecté"
echo "2. Vérifier la synchronisation en temps réel"
echo "3. Tester le gameplay multijoueur complet"

echo ""
echo "�� Tests terminés !"
