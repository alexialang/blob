<?php
namespace App\Service;

use App\Entity\CategoryQuiz;
use App\Entity\Quiz;
use App\Entity\User;
use App\Enum\Status;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizService
{
    private EntityManagerInterface $em;
    private QuizRepository $quizRepository;

    public function __construct(EntityManagerInterface $em, QuizRepository $quizRepository)
    {
        $this->em = $em;
        $this->quizRepository = $quizRepository;
    }

    public function list(): array
    {
        return $this->quizRepository->findAll();
    }

    public function create(array $data, User $user): Quiz
    {
        $quiz = new Quiz();
        $quiz->setTitle($data['title']);
        $quiz->setDescription($data['description']);
        $quiz->setStatus(Status::from($data['status']));
        $quiz->setIsPublic($data['is_public'] ?? false);
        $quiz->setDateCreation(new \DateTimeImmutable());
        $quiz->setUser($user);

        if (isset($data['category']) && $data['category'] instanceof CategoryQuiz) {
            $quiz->setCategory($data['category']);
        }

        $this->em->persist($quiz);
        $this->em->flush();

        return $quiz;
    }

    public function show(Quiz $quiz): Quiz
    {
        return $quiz;
    }

    public function update(Quiz $quiz, array $data): Quiz
    {
        if (isset($data['title'])) {
            $quiz->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $quiz->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $quiz->setStatus(Status::from($data['status']));
        }
        if (isset($data['is_public'])) {
            $quiz->setIsPublic($data['is_public']);
        }
        if (isset($data['category']) && $data['category'] instanceof CategoryQuiz) {
            $quiz->setCategory($data['category']);
        }

        $this->em->flush();

        return $quiz;
    }

    public function delete(Quiz $quiz): void
    {
        $this->em->remove($quiz);
        $this->em->flush();
    }
}
