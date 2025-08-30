# Makefile pour l'industrialisation des tests
.PHONY: help test test-all test-unit test-integration test-entities test-services test-security coverage quality clean build

# Variables
DOCKER_COMPOSE = docker compose
APP_CONTAINER = blob_server

help: ## Affiche l'aide
	@echo "Industrialisation des tests avec Docker"
	@echo ""
	@echo "Commandes disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Construit le container principal
	@echo "Construction du container principal..."
	$(DOCKER_COMPOSE) build server

test: ## Lance tous les tests avec PHPUnit
	@echo "Lancement de tous les tests..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit

test-all: ## Lance tous les tests PHPUnit
	@echo "Lancement de tous les tests PHPUnit..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit

test-unit: ## Lance les tests unitaires
	@echo "Lancement des tests unitaires..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit --testsuite "Project Test Suite"

test-integration: ## Lance les tests d'intégration
	@echo "Lancement des tests d'intégration..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit tests/Integration/

test-entities: ## Lance les tests d'entités
	@echo "Lancement des tests d'entités..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit tests/Entity/

test-services: ## Lance les tests de services
	@echo "Lancement des tests de services..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit tests/Service/

test-security: ## Lance les tests de sécurité
	@echo "Lancement des tests de sécurité..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit tests/Security/

coverage: ## Génère le rapport de couverture
	@echo "Génération du rapport de couverture..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit --coverage-html var/coverage/html --coverage-clover var/coverage/clover.xml

quality: ## Vérifie la qualité du code
	@echo "Vérification de la qualité du code..."
	@echo "PHP CS Fixer..."
	$(DOCKER_COMPOSE) exec server vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "PHPStan..."
	$(DOCKER_COMPOSE) exec server vendor/bin/phpstan analyse src/ --level=5
	@echo "Infection..."
	$(DOCKER_COMPOSE) exec server vendor/bin/infection --min-msi=80 --no-interaction
	@echo "Qualité du code vérifiée"

phpstan: ## Analyse statique avec PHPStan
	@echo "Analyse statique avec PHPStan..."
	$(DOCKER_COMPOSE) exec server vendor/bin/phpstan analyse src/ --level=5

phpcs: ## Vérification des standards de code
	@echo "Vérification des standards de code..."
	$(DOCKER_COMPOSE) exec server vendor/bin/php-cs-fixer fix --dry-run --diff

infection: ## Tests de mutation avec Infection
	@echo "Tests de mutation avec Infection..."
	$(DOCKER_COMPOSE) exec server vendor/bin/infection --min-msi=80 --no-interaction

clean: ## Nettoie les rapports et le cache
	@echo "Nettoyage des rapports et du cache..."
	@echo "Nettoyage local..."
	rm -rf var/test-reports/* var/coverage/* 2>/dev/null || true
	@echo "Nettoyage du container..."
	$(DOCKER_COMPOSE) exec server rm -rf var/cache/* var/logs/* 2>/dev/null || true
	@echo "Nettoyage terminé"

logs: ## Affiche les logs de l'application
	@echo "Logs de l'application..."
	$(DOCKER_COMPOSE) logs server

shell: ## Ouvre un shell dans le container principal
	@echo "Ouverture d'un shell dans le container principal..."
	$(DOCKER_COMPOSE) exec server /bin/bash

# Commandes d'industrialisation avancées
industrialization: ## Lance le processus complet d'industrialisation
	@echo "Lancement du processus d'industrialisation..."
	@make clean
	@make test-all
	@make coverage
	@make quality
	@echo "Industrialisation terminée"

ci: ## Mode CI/CD (sans interaction)
	@echo "Mode CI/CD..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit --no-interaction
	$(DOCKER_COMPOSE) exec server vendor/bin/php-cs-fixer fix --dry-run --no-interaction
	$(DOCKER_COMPOSE) exec server vendor/bin/phpstan analyse src/ --level=5 --no-interaction

# Commandes de développement
dev-test: ## Lance les tests en mode développement
	@echo "Tests en mode développement..."
	$(DOCKER_COMPOSE) exec server php bin/phpunit --stop-on-failure

# Commandes utilitaires
composer-install: ## Installe les dépendances Composer
	@echo "Installation des dépendances..."
	$(DOCKER_COMPOSE) exec server composer install

composer-update: ## Met à jour les dépendances Composer
	@echo "Mise à jour des dépendances..."
	$(DOCKER_COMPOSE) exec server composer update

# Aide contextuelle
test-help: ## Aide pour les tests
	@echo "Aide pour les tests:"
	@echo "  make test          - Tous les tests PHPUnit"
	@echo "  make test-all      - Tous les tests PHPUnit"
	@echo "  make test-unit     - Tests unitaires uniquement"
	@echo "  make test-integration - Tests d'intégration uniquement"
	@echo "  make coverage      - Rapport de couverture"
	@echo "  make quality       - Vérification qualité"
	@echo "  make clean         - Nettoyage"
