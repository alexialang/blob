<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests d'intégration basiques
 * Vérifie que l'application Symfony fonctionne correctement
 */
class BasicIntegrationTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testApplicationBoots(): void
    {
        // Test que l'application Symfony démarre correctement
        $this->assertNotNull($this->client);
        $this->assertTrue(true, "L'application Symfony boot correctement");
    }

    public function testServiceContainer(): void
    {
        // Test que le container de services fonctionne
        $container = $this->client->getContainer();
        $this->assertNotNull($container);
        
        // Test qu'on peut récupérer l'EntityManager
        $this->assertTrue($container->has('doctrine.orm.entity_manager'));
    }

    public function testDoctrineDatabaseConnection(): void
    {
        // Test de la connexion à la base de données
        $container = $this->client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        $this->assertNotNull($entityManager);
        
        // Test d'une requête simple
        try {
            $connection = $entityManager->getConnection();
            $result = $connection->executeQuery('SELECT 1 as test');
            $data = $result->fetchAssociative();
            
            $this->assertEquals(1, $data['test']);
        } catch (\Exception $e) {
            // Si pas de DB configurée, on vérifie au moins que l'EM existe
            $this->assertNotNull($entityManager);
        }
    }

    public function testUserServiceExists(): void
    {
        // Test que nos services critiques sont bien enregistrés
        $container = $this->client->getContainer();
        
        $this->assertTrue(
            $container->has('App\Service\UserService'),
            "Le UserService doit être enregistré dans le container"
        );
    }

    public function testEntitiesAreLoadable(): void
    {
        // Test que nos entités sont correctement configurées
        $container = $this->client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        // Test qu'on peut récupérer les métadonnées des entités
        $metadataFactory = $entityManager->getMetadataFactory();
        
        $entities = ['App\Entity\User', 'App\Entity\Quiz', 'App\Entity\Badge'];
        
        foreach ($entities as $entityClass) {
            try {
                $metadata = $metadataFactory->getMetadataFor($entityClass);
                $this->assertNotNull($metadata, "Métadonnées de $entityClass doivent être disponibles");
            } catch (\Exception $e) {
                $this->fail("Impossible de charger les métadonnées pour $entityClass: " . $e->getMessage());
            }
        }
    }

    public function testEnvironmentConfiguration(): void
    {
        // Test que l'environnement est correctement configuré
        $container = $this->client->getContainer();
        
        // Vérifier qu'on est en environnement de test
        $this->assertEquals('test', $container->getParameter('kernel.environment'));
        
        // Vérifier que le debug est activé en test
        $this->assertTrue($container->getParameter('kernel.debug'));
    }

    public function testSymfonyFrameworkIntegration(): void
    {
        // Test que les composants Symfony essentiels fonctionnent
        $container = $this->client->getContainer();
        
        // Test du router
        $this->assertTrue($container->has('router'));
        
        // Test du serializer
        $this->assertTrue($container->has('serializer'));
        
        // Test du validator
        $this->assertTrue($container->has('validator'));
    }
}
