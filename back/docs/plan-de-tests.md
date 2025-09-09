# Plan de Tests - Application Blob

## 1. Objectifs et périmètre

### 1.1 Objectifs
- Garantir la fiabilité et la robustesse de l'API backend Symfony
- Valider les fonctionnalités critiques métier (quiz, utilisateurs, badges)
- Assurer la sécurité des mécanismes d'authentification et d'autorisation
- Maintenir une couverture de code minimale de 50%

### 1.2 Périmètre fonctionnel
- **Modules testés** : Services métier, entités Doctrine, contrôleurs API
- **Fonctionnalités prioritaires** : Gestion des quiz, système de badges, authentification JWT
- **Composants de sécurité** : UserChecker, PermissionVoter, validation des entrées
- **Intégrations** : Base de données, conteneur de services Symfony

### 1.3 Périmètre technique
- **Environnement cible** : Backend Symfony 7.2, PHP 8.4
- **Types de tests** : Unitaires, intégration, sécurité
- **Outils** : PHPUnit 9.6, Xdebug (couverture), Symfony TestCase

## 2. Stratégie de tests

### 2.1 Approche pyramidale
```
    /\
   /  \    Tests E2E (0% - délégués au frontend)
  /____\   
 /      \   Tests d'intégration (14% - 135 tests)
/________\  Tests unitaires (86% - 783 tests)
```

### 2.2 Priorités de test
1. **Critique** : Services métier, sécurité, entités principales
2. **Important** : Contrôleurs API, repositories complexes
3. **Souhaitable** : Utilitaires, helpers, configurations

### 2.3 Critères de qualité
- **Couverture minimum** : 50% des lignes de code
- **Couverture services critiques** : 80%+ 
- **Couverture sécurité** : 100%
- **Tests edge cases** : Obligatoires pour les services métier

## 3. Types de tests

### 3.1 Tests unitaires
**Objectif** : Valider chaque composant individuellement

**Composants testés** :
- Services métier (BadgeService, UserService, CategoryQuizService, etc.)
- Entités Doctrine (User, Quiz, Badge, etc.)
- Énumérations et exceptions personnalisées
- Event listeners et handlers

**Approche** :
- Isolation complète avec mocks
- Validation comportements normaux et exceptionnels
- Tests des cas limites (valeurs nulles, négatives, etc.)

### 3.2 Tests de sécurité
**Objectif** : Garantir la robustesse des mécanismes de protection

**Composants testés** :
- UserChecker (vérification état utilisateur)
- PermissionVoter (contrôles d'autorisation)
- Validation des entrées utilisateur
- Gestion des exceptions de sécurité

**Scénarios testés** :
- Utilisateur non vérifié, inactif, supprimé
- Tentatives d'accès non autorisées
- Validation de tokens JWT invalides

### 3.3 Tests d'intégration
**Objectif** : Valider l'assemblage des composants

**Composants testés** :
- Connexion base de données
- Conteneur de services Symfony
- Configuration environnement test
- Chargement des services critiques

## 4. Environnements de test

### 4.1 Configuration
- **Environnement** : `APP_ENV=test`
- **Base de données** : SQLite dédiée (`var/test.db`)
- **Cache** : Désactivé pour isolation
- **Debug** : Activé pour diagnostics

### 4.2 Isolation
- **Sessions** : Mock file storage
- **Emails** : Pas d'envoi réel
- **Services externes** : Mocks systématiques
- **Données** : Fixtures contrôlées, pas de données réelles

## 5. Outils et technologies

### 5.1 Framework de test
- **PHPUnit 9.6.25** : Framework principal
- **Symfony TestCase** : Intégration Symfony
- **Doctrine Test** : Gestion base de données test

### 5.2 Couverture de code
- **Xdebug** : Génération métriques couverture
- **Rapports HTML** : Visualisation détaillée (`var/coverage/html`)
- **Rapports texte** : Intégration continue

### 5.3 Automatisation
```bash
# Exécution complète
vendor/bin/phpunit

# Avec couverture
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text

# Génération rapport HTML
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=var/coverage/html
```

## 6. Métriques et objectifs

### 6.1 Objectifs quantitatifs
- **Tests unitaires** : 783 tests implémentés
- **Tests d'intégration** : 135 tests implémentés  
- **Total** : 918 tests (répartition 86% unitaires / 14% intégration)
- **Couverture globale** : 50,92% (objectif minimal 50% atteint)
- **Couverture services critiques** : 80-100% selon priorité métier
- **Couverture sécurité** : 100% sur composants critiques

### 6.2 Métriques qualitatives
- **Stabilité** : Tests robustes et reproductibles
- **Performance** : Exécution rapide de la suite complète
- **Maintenabilité** : Code de test lisible et bien structuré
- **Documentation** : Noms de tests explicites et auto-documentés

## 7. Planification et responsabilités

### 7.1 Phases de test
1. **Phase 1** : Tests unitaires services critiques (terminée)
2. **Phase 2** : Tests sécurité et authentification (terminée)
3. **Phase 3** : Tests d'intégration infrastructure (terminée)
4. **Phase 4** : Optimisation couverture et edge cases (terminée)

### 7.2 Responsabilités
- **Développeur** : Implémentation tests unitaires et d'intégration
- **Validation** : Exécution suite complète avant chaque commit
- **Maintenance** : Mise à jour tests lors évolutions fonctionnelles

## 8. Risques et mitigation

### 8.1 Risques identifiés
- **Tests flaky** : Dépendances temporelles ou aléatoires
- **Couplage fort** : Tests trop liés à l'implémentation
- **Couverture trompeuse** : Code couvert mais mal testé

### 8.2 Stratégies de mitigation
- **Isolation stricte** : Mocks pour toutes les dépendances externes
- **Tests comportementaux** : Focus sur le résultat, pas l'implémentation
- **Revue qualitative** : Validation manuelle des zones critiques

## 9. Évolutions futures

### 9.1 Améliorations prévues
- **Tests E2E** : Délégués au frontend Angular (Cypress/Protractor)
- **Tests de performance** : Validation temps de réponse API
- **Tests de charge** : Validation comportement sous contrainte
- **Tests de sécurité avancés** : Audit automatisé vulnérabilités

### 9.2 Outils complémentaires
- **Codeception** : Tests fonctionnels
- **PHPBench** : Tests de performance
- **Security Checker** : Audit sécurité
- **Mutation Testing** : Validation qualité tests

---

**Document créé le** : Septembre 2024  
**Version** : 1.0  
**Statut** : Implémenté
