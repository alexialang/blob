<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class BadgeService
{
    private EntityManagerInterface $em;
    private BadgeRepository $badgeRepository;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $em, BadgeRepository $badgeRepository, UserRepository $userRepository, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->badgeRepository = $badgeRepository;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    public function list(): array
    {
        return $this->badgeRepository->findAll();
    }

    public function find(int $id): ?Badge
    {
        return $this->badgeRepository->find($id);
    }


    public function delete(Badge $badge): void
    {
        $this->validateBadgeEntity($badge);
        
        $this->em->beginTransaction();
        
        try {
            $this->em->remove($badge);
            $this->em->flush();
            $this->em->commit();
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \RuntimeException('Erreur lors de la suppression du badge: ' . $e->getMessage());
        }
    }

    public function awardBadge(User $user, string $badgeName): bool
    {
        $this->validateAwardBadgeData($user, $badgeName);
        
        $this->em->beginTransaction();
        
        try {
            foreach ($user->getBadges() as $badge) {
                if ($badge->getName() === $badgeName) {
                    $this->em->rollback();
                    return false;
                }
            }

            $badge = $this->badgeRepository->findOneBy(['name' => $badgeName]);
            if (!$badge) {
                $this->em->rollback();
                return false;
            }

            $user->addBadge($badge);
            $this->em->flush();
            $this->em->commit();

            return true;
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \RuntimeException('Erreur lors de l\'attribution du badge: ' . $e->getMessage());
        }
    }

    public function initializeBadges(): void
    {
        $badges = $this->getValidatedBadgeData();
        
        $this->em->beginTransaction();
        
        try {
            foreach ($badges as $badgeData) {
                $this->validateBadgeData($badgeData);
                
                $existingBadge = $this->badgeRepository->findOneBy(['name' => $badgeData['name']]);
                if (!$existingBadge) {
                    $badge = new Badge();
                    $badge->setName($badgeData['name']);
                    $badge->setDescription($badgeData['description'] ?? '');
                    $badge->setImage($badgeData['image'] ?? '');

                    $this->em->persist($badge);
                }
            }
            
            $this->em->flush();
            $this->em->commit();
            
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \RuntimeException('Erreur lors de l\'initialisation des badges: ' . $e->getMessage());
        }
    }

    /**
     * Valide les données d'un badge
     */
    private function validateBadgeData(array $data): void
    {
        $constraints = new Collection([
            'name' => [
                new NotBlank(['message' => 'Le nom du badge est obligatoire']),
                new Length(['min' => 2, 'max' => 100, 'minMessage' => 'Le nom doit contenir au moins 2 caractères', 'maxMessage' => 'Le nom ne peut pas dépasser 100 caractères'])
            ],
            'description' => [
                new NotBlank(['message' => 'La description du badge est obligatoire']),
                new Length(['min' => 10, 'max' => 500, 'minMessage' => 'La description doit contenir au moins 10 caractères', 'maxMessage' => 'La description ne peut pas dépasser 500 caractères'])
            ],
            'image' => [
                new NotBlank(['message' => 'L\'image du badge est obligatoire']),
                new Length(['max' => 255, 'maxMessage' => 'Le nom de l\'image ne peut pas dépasser 255 caractères'])
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    /**
     * Valide les paramètres pour l'attribution d'un badge
     */
    private function validateAwardBadgeData(User $user, string $badgeName): void
    {
        if (!$user) {
            throw new \InvalidArgumentException('L\'utilisateur ne peut pas être null');
        }
        
        if (empty(trim($badgeName))) {
            throw new \InvalidArgumentException('Le nom du badge ne peut pas être vide');
        }
        
        if (strlen($badgeName) > 100) {
            throw new \InvalidArgumentException('Le nom du badge ne peut pas dépasser 100 caractères');
        }
    }

    /**
     * Valide l'entité Badge
     */
    private function validateBadgeEntity(Badge $badge): void
    {
        $errors = $this->validator->validate($badge);
        if (count($errors) > 0) {
            throw new ValidationFailedException($badge, $errors);
        }
    }

    /**
     * Retourne les données validées des badges par défaut
     */
    private function getValidatedBadgeData(): array
    {
        return [
            [
                'name' => 'Premier Quiz',
                'description' => 'Félicitations ! Vous avez créé votre premier quiz.',
                'image' => 'badge-first-quiz.png'
            ],
            [
                'name' => 'Quiz Master',
                'description' => 'Impressionnant ! Vous avez créé 10 quiz.',
                'image' => 'badge-quiz-master.png'
            ],
            [
                'name' => 'Première Victoire',
                'description' => 'Bravo ! Vous avez terminé votre premier quiz.',
                'image' => 'badge-first-victory.png'
            ],
            [
                'name' => 'Expert',
                'description' => 'Parfait ! Vous avez obtenu un score de 100%.',
                'image' => 'badge-expert.png'
            ],
            [
                'name' => 'Joueur Assidu',
                'description' => 'Incroyable ! Vous avez joué 50 quiz.',
                'image' => 'badge-dedicated-player.png'
            ]
        ];
    }
}
