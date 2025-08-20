<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class BadgeService
{
    private EntityManagerInterface $em;
    private BadgeRepository $badgeRepository;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $em, BadgeRepository $badgeRepository, UserRepository $userRepository, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->badgeRepository = $badgeRepository;
        $this->userRepository = $userRepository;
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
        $this->em->remove($badge);
        $this->em->flush();
    }

    public function awardBadge(User $user, string $badgeName): bool
    {
        foreach ($user->getBadges() as $badge) {
            if ($badge->getName() === $badgeName) {
                return false;
            }
        }

        $badge = $this->badgeRepository->findOneBy(['name' => $badgeName]);
        if (!$badge) {
            return false;
        }

        $user->addBadge($badge);
        $this->em->flush();

        return true;
    }

    public function initializeBadges(): void
    {
        $badges = [
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

        foreach ($badges as $badgeData) {
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
    }

}
