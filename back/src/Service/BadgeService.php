<?php

namespace App\Service;

use App\Entity\Badge;
use App\Repository\BadgeRepository;
use Doctrine\ORM\EntityManagerInterface;

class BadgeService
{
    private EntityManagerInterface $em;
    private BadgeRepository $badgeRepository;

    public function __construct(EntityManagerInterface $em, BadgeRepository $badgeRepository)
    {
        $this->em = $em;
        $this->badgeRepository = $badgeRepository;
    }

    public function list(): array
    {
        return $this->badgeRepository->findAll();
    }

    public function find(int $id): ?Badge
    {
        return $this->badgeRepository->find($id);
    }

    public function create(array $data): Badge
    {
        $badge = new Badge();
        $badge->setName($data['name']);
        $badge->setDescription($data['description'] ?? '');
        $badge->setImage($data['image'] ?? '');

        $this->em->persist($badge);
        $this->em->flush();

        return $badge;
    }

    public function update(Badge $badge, array $data): Badge
    {
        if (isset($data['name'])) {
            $badge->setName($data['name']);
        }
        if (isset($data['description'])) {
            $badge->setDescription($data['description']);
        }
        if (isset($data['image'])) {
            $badge->setImage($data['image']);
        }

        $this->em->flush();

        return $badge;
    }

    public function delete(Badge $badge): void
    {
        $this->em->remove($badge);
        $this->em->flush();
    }
}
