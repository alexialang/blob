# Plan de Tests Backend - Application Quiz

## 🎯 Objectifs
- Atteindre **minimum 50% de couverture de code**
- Assurer la qualité et la fiabilité du backend Symfony
- Couvrir les fonctionnalités critiques métier

## 📊 État Actuel
- **Fichiers source :** 102 fichiers PHP
- **Fichiers de tests :** 86 tests existants  
- **Couverture actuelle :** 0% (tests en erreur - configuration à corriger)
- **Tests en échec :** 407 erreurs + 58 failures

## 🏗️ Architecture Testée

### 1. Entités (Domain Layer)
**Couverture cible : 80%**

- ✅ `User.php` - Entité utilisateur principale
- ✅ `Quiz.php` - Entité quiz avec relations
- ✅ `Question.php` - Questions et réponses
- ✅ `Company.php` - Gestion des entreprises
- ✅ `Group.php` - Groupes d'utilisateurs
- ✅ `GameSession.php` - Sessions multijoueur
- ✅ `Badge.php` - Système de badges
- ✅ `UserPermission.php` - Gestion des permissions

**Tests prioritaires :**
- Validation des contraintes
- Relations entre entités
- Getters/Setters critiques
- Méthodes métier

### 2. Services (Business Logic)
**Couverture cible : 70%**

#### Services Critiques (Priorité 1)
- ✅ `UserService.php` - Gestion utilisateurs
- ✅ `QuizCrudService.php` - CRUD des quiz
- ✅ `QuizSearchService.php` - Recherche et filtres
- ✅ `CompanyService.php` - Gestion entreprises
- ✅ `UserPermissionService.php` - Sécurité/permissions
- ✅ `PaymentService.php` - Intégration Stripe
- ✅ `UserPasswordResetService.php` - Sécurité

#### Services Fonctionnels (Priorité 2)
- ✅ `MultiplayerGameService.php` - Logique multijoueur
- ✅ `GroupService.php` - Gestion groupes
- ✅ `BadgeService.php` - Système de récompenses
- ✅ `LeaderboardService.php` - Classements
- ✅ `UserAnswerService.php` - Réponses utilisateurs

#### Services Utilitaires (Priorité 3)
- ✅ `GlobalStatisticsService.php` - Statistiques
- ✅ `QuizRatingService.php` - Évaluations
- ✅ `MultiplayerConfigService.php` - Configuration

### 3. Controllers (API Layer)
**Couverture cible : 60%**

#### Endpoints Critiques
- ✅ `UserController.php` - API utilisateurs
- ✅ `QuizController.php` - API quiz
- ✅ `CompanyController.php` - API entreprises
- ✅ `PasswordResetController.php` - Sécurité
- ✅ `StripeWebhookController.php` - Webhooks paiement

#### Endpoints Fonctionnels
- ✅ `GroupController.php` - API groupes
- ✅ `UserPermissionController.php` - API permissions
- ✅ `MultiplayerGameController.php` - API multijoueur
- ✅ `UserAnswerController.php` - API réponses

#### Endpoints Utilitaires
- ✅ `StatusController.php` - Health checks
- ✅ `TypeQuestionController.php` - Types de questions
- ✅ `BadgeController.php` - API badges

### 4. Repositories
**Couverture cible : 40%**
- Tests des requêtes personnalisées critiques
- Méthodes de recherche avancée

## 🧪 Types de Tests

### 1. Tests Unitaires (Unit Tests)
- **Entités :** Validation, relations, méthodes métier
- **Services :** Logique métier isolée avec mocks
- **Validators :** Règles de validation personnalisées

### 2. Tests d'Intégration (Integration Tests)
- **Services + Repository :** Tests avec base de données
- **API Endpoints :** Tests fonctionnels avec Symfony Client
- **Workflows complets :** Inscription → Quiz → Résultats

### 3. Tests Fonctionnels (Functional Tests)
- **Authentification JWT**
- **Autorisation/Permissions**
- **Workflows métier critiques**
- **Intégrations externes (Stripe)**

## 📋 Plan d'Implémentation

### Phase 1 : Correction des Tests Existants
1. **Fixer les mocks défaillants**
   - UserService constructor issues
   - QuizSearchService dependencies
   - Service validation problems

2. **Corriger les tests Controllers**
   - Routes 404 → Vérifier routing
   - Authentication setup
   - JWT configuration

3. **Stabiliser les tests Entities**
   - Mock expectations
   - Collection handling

### Phase 2 : Tests Unitaires Prioritaires
1. **Entités Core** (User, Quiz, Company)
2. **Services Critiques** (UserService, QuizCrudService)
3. **Validators** (CustomConstraints)

### Phase 3 : Tests d'Intégration
1. **API Authentication**
2. **CRUD Operations**
3. **Business Workflows**

### Phase 4 : Optimisation Couverture
1. **Repository methods**
2. **Edge cases**
3. **Error handling**

## 🎯 Métriques de Succès

### Couverture par Composant
- **Entités :** 80% minimum
- **Services :** 70% minimum  
- **Controllers :** 60% minimum
- **Global :** 50% minimum

### Qualité
- Aucun test en échec
- Temps d'exécution < 2 minutes
- Code coverage stable
- Documentation à jour

## 🔧 Outils Utilisés

- **PHPUnit 9.6** - Framework de tests
- **Symfony Test Client** - Tests fonctionnels
- **PHP Code Coverage** - Mesure couverture
- **PHPStan** - Analyse statique
- **Infection** - Tests de mutation (optionnel)

## 📝 Configuration Tests

### Base de Données
- Environnement `test` isolé
- Fixtures pour données de test
- Transactions rollback après chaque test

### Mocking
- Doctrine ORM mocks
- Service dependencies
- External APIs (Stripe, Mail)

### CI/CD
- Tests automatiques sur push
- Rapports de couverture
- Quality gates


