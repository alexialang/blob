<?php

namespace App\Service;

use App\Entity\TypeQuestion;
use App\Repository\TypeQuestionRepository;
use App\Enum\TypeQuestionName;
use Doctrine\ORM\EntityManagerInterface;

class TypeQuestionService
{
    private EntityManagerInterface $em;
    private TypeQuestionRepository $typeQuestionRepository;

    public function __construct(EntityManagerInterface $em, TypeQuestionRepository $typeQuestionRepository)
    {
        $this->em = $em;
        $this->typeQuestionRepository = $typeQuestionRepository;
    }

    public function list(): array
    {
        return $this->typeQuestionRepository->findAll();
    }

    public function create(array $data): TypeQuestion
    {
        $name = $data['name'] ?? null;

        if (!$name || !$this->isValidTypeName($name)) {
            throw new \InvalidArgumentException('Invalid type of question name.');
        }

        $typeQuestion = new TypeQuestion();
        $typeQuestion->setName($name);

        $this->em->persist($typeQuestion);
        $this->em->flush();

        return $typeQuestion;
    }

    public function show(TypeQuestion $typeQuestion): TypeQuestion
    {
        return $typeQuestion;
    }

    public function update(TypeQuestion $typeQuestion, array $data): TypeQuestion
    {
        if (isset($data['name']) && $this->isValidTypeName($data['name'])) {
            $typeQuestion->setName($data['name']);
        }

        $this->em->flush();

        return $typeQuestion;
    }

    public function delete(TypeQuestion $typeQuestion): void
    {
        $this->em->remove($typeQuestion);
        $this->em->flush();
    }

    private function isValidTypeName(string $name): bool
    {
        return in_array($name, array_column(TypeQuestionName::cases(), 'value'), true);
    }
}
