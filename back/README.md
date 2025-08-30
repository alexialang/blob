# Blob - Plateforme de Quiz Interactive

## Statut du Projet

[![CI/CD Pipeline](https://github.com/[VOTRE_USERNAME]/projet-fil-rouge-alexialang/workflows/CI%2FCD%20Pipeline%20-%20Qualité%20et%20Tests/badge.svg)](https://github.com/[VOTRE_USERNAME]/projet-fil-rouge-alexialang/actions)
[![Tests](https://img.shields.io/badge/Tests-101%20passed-brightgreen)](https://github.com/[VOTRE_USERNAME]/projet-fil-rouge-alexialang/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%205-blue)](https://github.com/[VOTRE_USERNAME]/projet-fil-rouge-alexialang/actions)
[![Code Style](https://img.shields.io/badge/Code%20Style-PSR12-yellow)](https://github.com/[VOTRE_USERNAME]/projet-fil-rouge-alexialang/actions)

## Industrialisation Complète

### Outils QA Intégrés :
- **PHPUnit** - Tests automatisés (101 tests)
- **PHPStan** - Analyse statique (Niveau 5)
- **PHP CS Fixer** - Standards de code (PSR-12)
- **Infection** - Tests de mutation (MSI 80%)

### Pipeline CI/CD Automatique :
- **Tests automatiques** à chaque push
- **Qualité du code** vérifiée automatiquement
- **Sécurité** analysée à chaque build
- **Build Docker** automatisé (local)
- **Rapports de qualité** générés

## Commandes Locales

### Tests :
```bash
make test-all          # Tous les tests
make test-services     # Tests de services
make test-entities     # Tests d'entités
make test-security     # Tests de sécurité
make coverage          # Rapport de couverture
```

### Qualité :
```bash
make quality           # Vérification complète
make phpstan           # Analyse statique
make phpcs             # Standards de code
make infection         # Tests de mutation
```

### Industrialisation :
```bash
make industrialization # Processus complet
make ci                # Mode CI/CD
make clean             # Nettoyage
```

## Démarrage Rapide

### 1. Construction :
```bash
make build
```

### 2. Démarrage :
```bash
docker compose up -d
```

### 3. Tests :
```bash
make test-all
```

### 4. Qualité :
```bash
make quality
```

## Prérequis

- Docker & Docker Compose
- PHP 8.3+
- Composer
- Make

## Configuration

### Variables d'environnement :
```bash
APP_ENV=dev
DATABASE_URL=mysql://user:password@localhost:3306/blob_db
```

### Outils QA :
- **PHPStan** : `config/packages/phpstan.neon`
- **PHP CS Fixer** : `config/packages/php_cs_fixer.yaml`
- **Infection** : `infection.json.dist`

## Métriques

- **Couverture de code** : En cours de calcul
- **Qualité du code** : Niveau 5 PHPStan
- **Standards** : PSR-12 respectés
- **Tests** : 101 tests passants

## Objectifs

- **Tests automatisés** - 50%+ de couverture
- **Qualité garantie** - Standards PSR-12
- **Sécurité renforcée** - Analyse automatique
- **Pipeline CI/CD** - Qualité et tests automatiques

## Documentation

- [Guide des tests](docs/TESTS.md)
- [Standards de code](docs/CODING_STANDARDS.md)
- [Pipeline CI/CD](docs/CI_CD.md)

---

**Industrialisation professionnelle en place**
**Pipeline CI/CD : Tests + Qualité + Sécurité + Build Docker**
