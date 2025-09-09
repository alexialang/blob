# Documentation CI/CD - Projet Blob Quiz

## 📋 Aperçu

Ce document décrit la configuration CI/CD complète mise en place pour le projet Blob Quiz, incluant les tests automatisés, la sécurité, et le déploiement.

## 🚀 Pipeline CI/CD

### Structure du Pipeline

Le pipeline CI/CD est configuré avec **GitHub Actions** et comprend les étapes suivantes :

1. **Tests Backend** (PHP/Symfony)
2. **Tests Frontend** (Angular/TypeScript)
3. **Analyse de Sécurité** (Trivy)
4. **Build des Images Docker**
5. **Déploiement Staging/Production**

### Configuration des Branches

- **`main`** : Déploiement automatique en production
- **`dev`** : Déploiement automatique en staging
- **Pull Requests** : Exécution des tests uniquement

## 🧪 Tests Mis en Place

### Tests Frontend

#### Tests Unitaires
- **Services** : `QuizGameService`, `QuizResultsService`, `MultiplayerService`
- **Composants** : `QuizGameComponent`, `MultiplayerGameComponent`, `McqQuestionComponent`
- **Guards** : `AuthGuard`, `PermissionGuard`
- **Interceptors** : `AuthInterceptor`

#### Tests d'Intégration
- Configuration Karma avec couverture de code
- Seuil de couverture : 80% (lignes, branches, fonctions)
- Tests en mode headless pour CI

#### Tests E2E
- **Protractor** configuré pour tests end-to-end
- Scénarios couverts :
  - Flux d'authentification
  - Jeu de quiz complet
  - Mode multijoueur
  - Fonctionnalités admin
  - Design responsive

### Tests de Performance
- **K6** pour les tests de charge
- Scénarios :
  - Charge normale (10 utilisateurs)
  - Test de stress (jusqu'à 100 utilisateurs)
  - Tests multiplayer (WebSockets)

## 🔒 Sécurité

### Analyse Statique
- **Trivy** pour la détection de vulnérabilités
- Scan des dépendances et du code
- Intégration avec GitHub Security Tab

### Linting
- **ESLint** pour le code TypeScript/Angular
- **PHPStan** pour l'analyse statique PHP
- Règles de qualité de code strictes

## 🐳 Containerisation

### Images Docker
- **Backend** : PHP 8.3 + Nginx
- **Frontend** : Node.js 20 + Nginx (production)
- Images multi-stage pour optimisation

### Docker Compose
- **`docker-compose.yml`** : Développement local
- **`docker-compose.ci.yml`** : Tests CI/CD
- Services : Application, Base de données, Redis, RabbitMQ, Mercure

## 📊 Monitoring et Métriques

### Couverture de Code
- **Backend** : PHPUnit avec Codecov
- **Frontend** : Karma + Istanbul avec Codecov
- Rapports automatiques sur les PRs

### Métriques de Performance
- Temps de réponse API < 500ms (95e percentile)
- Taux d'erreur < 10%
- Disponibilité > 99.5%

## 🚀 Déploiement

### Environnements

#### Staging (`dev` branch)
- URL : `https://staging.blob-quiz.com`
- Base de données de test
- Déploiement automatique

#### Production (`main` branch)
- URL : `https://blob-quiz.com`
- Base de données de production
- Déploiement avec validation manuelle

### Secrets Requis

```bash
# Docker Hub
DOCKER_USERNAME=your_dockerhub_username
DOCKER_PASSWORD=your_dockerhub_token

# Serveurs de déploiement
STAGING_HOST=staging.blob-quiz.com
STAGING_USERNAME=deploy
STAGING_SSH_KEY=-----BEGIN PRIVATE KEY-----...

PRODUCTION_HOST=blob-quiz.com
PRODUCTION_USERNAME=deploy
PRODUCTION_SSH_KEY=-----BEGIN PRIVATE KEY-----...

# Notifications
SLACK_WEBHOOK=https://hooks.slack.com/services/...
```

## 📝 Scripts Utiles

### Lancement des Tests Localement

```bash
# Tests Backend
cd back
php bin/phpunit

# Tests Frontend
cd front
npm run test
npm run test:ci  # Mode CI
npm run e2e      # Tests E2E

# Tests de Performance
docker-compose -f docker-compose.ci.yml up performance-test
```

### Build Local

```bash
# Build complet avec tests
docker-compose -f docker-compose.ci.yml up --build

# Build production
docker-compose build --target prod
```

## 🔧 Configuration des Outils

### Karma (Tests Frontend)
```javascript
// karma.conf.js
coverageReporter: {
  check: {
    global: {
      statements: 80,
      branches: 80,
      functions: 80,
      lines: 80
    }
  }
}
```

### ESLint (Quality Code)
```json
{
  "rules": {
    "@typescript-eslint/no-unused-vars": "error",
    "@typescript-eslint/no-explicit-any": "warn",
    "prefer-const": "error"
  }
}
```

## 🎯 Bonnes Pratiques

### Développement
1. **Tests First** : Écrire les tests avant le code
2. **Couverture** : Maintenir > 80% de couverture
3. **Linting** : Code propre et cohérent
4. **Documentation** : Commenter les fonctions complexes

### CI/CD
1. **Tests Rapides** : Optimiser les temps d'exécution
2. **Feedback Immédiat** : Notifications sur échecs
3. **Rollback** : Possibilité de retour arrière rapide
4. **Monitoring** : Surveiller les métriques post-déploiement

### Sécurité
1. **Secrets** : Jamais dans le code source
2. **Vulnérabilités** : Scan automatique des dépendances
3. **HTTPS** : Toujours en production
4. **Authentification** : JWT avec expiration

## 📈 Métriques de Qualité

| Métrique | Objectif | Actuel |
|----------|----------|---------|
| Couverture Backend | > 80% | ✅ |
| Couverture Frontend | > 80% | ✅ |
| Temps de Build | < 10 min | ✅ |
| Temps de Tests | < 5 min | ✅ |
| Vulnérabilités Critiques | 0 | ✅ |

## 🆘 Dépannage

### Problèmes Courants

#### Tests Frontend Échouent
```bash
# Vérifier les dépendances
npm ci
# Relancer Chrome en mode headless
npm run test -- --browsers=ChromeHeadlessCI
```

#### Build Docker Échoue
```bash
# Nettoyer les images
docker system prune -a
# Reconstruire
docker-compose build --no-cache
```

#### Déploiement Bloqué
```bash
# Vérifier les logs
docker-compose logs -f
# Redémarrer les services
docker-compose restart
```

## 📞 Support

- **Issues** : Créer une issue GitHub pour les bugs
- **Discussion** : Utiliser les GitHub Discussions
- **Urgent** : Contact direct équipe DevOps

---

*Dernière mise à jour : 24 janvier 2025*

