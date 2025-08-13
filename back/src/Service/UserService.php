<?php

namespace App\Service;

use App\Entity\User;
use App\Message\Mailer\RegistrationConfirmationEmailMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class UserService
{
    private EntityManagerInterface      $em;
    private UserRepository              $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private MessageBusInterface         $bus;
    private MailerInterface             $mailer;
    private string                      $mailerFrom;
    private string                      $frontendUrl;

    public function __construct(
        #[Autowire('%mailer_from%')]  string       $mailerFrom,
        EntityManagerInterface        $em,
        UserRepository                $userRepository,
        UserPasswordHasherInterface   $passwordHasher,
        MessageBusInterface           $bus,
        MailerInterface               $mailer,
        ParameterBagInterface         $params

    ) {
        $this->em             = $em;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->bus            = $bus;
        $this->mailer         = $mailer;
        $this->mailerFrom     = $mailerFrom;

        $this->frontendUrl = rtrim($params->get('app.frontend_url'), '/');
    }

    public function list(bool $includeDeleted = false): array
    {
        if ($includeDeleted) {
            return $this->userRepository->findAll();
        }

        return $this->userRepository->findBy(['deletedAt' => null]);
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): User
    {

        foreach (['firstName', 'lastName', 'email', 'password'] as $field) {
            if (empty($data[$field]) || !\is_string($data[$field])) {
                throw new \InvalidArgumentException(sprintf('Le champ "%s" est obligatoire.', $field));
            }
        }


        $existing = $this->userRepository->findOneBy(['email' => $data['email']]);
        if (null !== $existing) {
            throw new \InvalidArgumentException('Cette adresse e-mail est déjà utilisée.');
        }


        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setDateRegistration(new \DateTimeImmutable());
        $user->setLastAccess(new \DateTime());
        $user->setRoles(['ROLE_USER']);
        $user->setIsAdmin($data['is_admin'] ?? false);
        $user->setIsActive(true);


        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $token = Uuid::v4()->toRfc4122();
        $user->setConfirmationToken($token);
        $user->setIsVerified(false);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['email']) && \is_string($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['firstName']) && \is_string($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName']) && \is_string($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['roles']) && \is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        if (isset($data['password']) && \is_string($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }
        if (isset($data['is_admin'])) {
            $user->setIsAdmin((bool) $data['is_admin']);
        }
        if (isset($data['lastAccess'])) {
            $user->setLastAccess(new \DateTime($data['lastAccess']));
        }
        if (isset($data['isActive'])) {
            $user->setIsActive((bool) $data['isActive']);
        }

        $this->em->flush();
        return $user;
    }


    public function delete(User $user): void
    {
        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setIsActive(false);

        $user->setEmail('deleted_user_' . $user->getId() . '@example.com');
        $user->setFirstName('Deleted');
        $user->setLastName('User');
        $user->setPassword('');
        $user->setRoles([]);
        $user->setIsAdmin(false);

        $this->em->flush();
    }

    public function sendQueueEmail(User $user): void
    {
        $message = new RegistrationConfirmationEmailMessage(
            $user->getEmail(),
            $user->getFirstName(),
            (string) $user->getConfirmationToken()
        );
        $this->bus->dispatch($message);
    }


    public function sendEmail(string $email, string $firstName, string $confirmationToken): void
    {
        $confirmationUrl = $this->frontendUrl . '/confirmation-compte/' . $confirmationToken;

        $mail = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('Merci de confirmer votre inscription')
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context([
                'firstName'       => $firstName,
                'confirmationUrl' => $confirmationUrl,
            ]);

        $this->mailer->send($mail);
    }

    public function confirmToken(string $token): ?User
    {
        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);
        
        if (null === $user) {
            return null;
        }

        if ($user->isVerified()) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        $this->em->flush();
        
        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['pseudo']) && \is_string($data['pseudo'])) {
            $user->setPseudo($data['pseudo']);
        }
        if (isset($data['firstName']) && \is_string($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName']) && \is_string($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['avatar']) && \is_string($data['avatar'])) {
            $user->setAvatar($data['avatar']);
        }
        
        // Gérer les données d'avatar séparées (shape et color)
        if (isset($data['avatarShape']) && isset($data['avatarColor'])) {
            $avatarData = [
                'shape' => $data['avatarShape'],
                'color' => $data['avatarColor']
            ];
            $user->setAvatar(json_encode($avatarData));
        }

        $this->em->flush();
        return $user;
    }

    public function getUserStatistics(User $user): array
    {
        $totalQuizzesCreated = $user->getQuizs()->count();
        $totalQuizzesCompleted = $user->getUserAnswers()->count();
        
        $totalScore = 0;
        $scoreHistory = [];
        $categoryPerformance = [];
        
        foreach ($user->getUserAnswers() as $userAnswer) {
            $score = $userAnswer->getTotalScore() ?? 0;
            $totalScore += $score;
            
            // Historique des scores avec date
            $scoreHistory[] = [
                'date' => $userAnswer->getDateAttempt()->format('Y-m-d'),
                'score' => $score,
                'quizTitle' => $userAnswer->getQuiz()?->getTitle() ?? 'Quiz inconnu'
            ];
            
            // Performance par catégorie (si disponible)
            if ($userAnswer->getQuiz() && $userAnswer->getQuiz()->getCategory()) {
                $categoryName = $userAnswer->getQuiz()->getCategory()->getName();
                if (!isset($categoryPerformance[$categoryName])) {
                    $categoryPerformance[$categoryName] = ['total' => 0, 'count' => 0];
                }
                $categoryPerformance[$categoryName]['total'] += $score;
                $categoryPerformance[$categoryName]['count']++;
            }
        }
        
        // Trier l'historique par date
        usort($scoreHistory, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        // Calculer les moyennes par catégorie
        $categoryAverages = [];
        foreach ($categoryPerformance as $category => $data) {
            $categoryAverages[] = [
                'category' => $category,
                'average' => round($data['total'] / $data['count'], 1),
                'count' => $data['count']
            ];
        }
        
        $averageScore = $totalQuizzesCompleted > 0 ? round($totalScore / $totalQuizzesCompleted, 1) : 0;
        $badgesEarned = $user->getBadges()->count();
        
        return [
            'totalQuizzesCreated' => $totalQuizzesCreated,
            'totalQuizzesCompleted' => $totalQuizzesCompleted,
            'totalScore' => $totalScore,
            'averageScore' => $averageScore,
            'badgesEarned' => $badgesEarned,
            'memberSince' => $user->getDateRegistration()->format('Y-m-d'),
            'lastAccess' => $user->getLastAccess()?->format('Y-m-d H:i:s'),
            'scoreHistory' => $scoreHistory,
            'categoryPerformance' => $categoryAverages
        ];
    }

    public function getLeaderboard(int $limit, User $currentUser): array
    {
        // Récupérer tous les utilisateurs actifs avec leurs scores
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
            ->andWhere('u.isActive = true')
            ->getQuery()
            ->getResult();

        $leaderboardData = [];
        
        foreach ($users as $user) {
            $totalScore = 0;
            $quizzesCompleted = 0;
            $totalAnswers = $user->getUserAnswers()->count();
            
            foreach ($user->getUserAnswers() as $userAnswer) {
                $totalScore += $userAnswer->getTotalScore() ?? 0;
                $quizzesCompleted++;
            }
            
            $averageScore = $quizzesCompleted > 0 ? round($totalScore / $quizzesCompleted, 1) : 0;
            $badgesCount = $user->getBadges()->count();
            
            // Calculer un score de classement (combinaison de total, moyenne et badges)
            $rankingScore = $totalScore + ($averageScore * 2) + ($badgesCount * 50);
            
            $leaderboardData[] = [
                'id' => $user->getId(),
                'pseudo' => $user->getPseudo() ?: ($user->getFirstName() . ' ' . substr($user->getLastName(), 0, 1) . '.'),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'avatar' => $user->getAvatar() ?: 'avatar-1',
                'totalScore' => $totalScore,
                'averageScore' => $averageScore,
                'quizzesCompleted' => $quizzesCompleted,
                'badgesCount' => $badgesCount,
                'rankingScore' => $rankingScore,
                'memberSince' => $user->getDateRegistration()->format('Y-m-d'),
                'isCurrentUser' => $user->getId() === $currentUser->getId()
            ];
        }
        
        // Trier par score de classement décroissant
        usort($leaderboardData, function($a, $b) {
            return $b['rankingScore'] - $a['rankingScore'];
        });
        
        // Ajouter les positions
        foreach ($leaderboardData as $index => &$userData) {
            $userData['position'] = $index + 1;
        }
        
        // Trouver la position de l'utilisateur actuel
        $currentUserPosition = null;
        $currentUserData = null;
        foreach ($leaderboardData as $userData) {
            if ($userData['isCurrentUser']) {
                $currentUserPosition = $userData['position'];
                $currentUserData = $userData;
                break;
            }
        }
        
        // Limiter aux N premiers utilisateurs
        $topUsers = array_slice($leaderboardData, 0, $limit);
        
        return [
            'leaderboard' => $topUsers,
            'currentUser' => [
                'position' => $currentUserPosition,
                'data' => $currentUserData,
                'totalUsers' => count($leaderboardData)
            ],
            'meta' => [
                'totalUsers' => count($leaderboardData),
                'limit' => $limit,
                'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ];
    }

    public function getGameHistory(User $user): array
    {
        $userAnswers = $this->em->getRepository(\App\Entity\UserAnswer::class)
            ->createQueryBuilder('ua')
            ->select('ua', 'q')
            ->leftJoin('ua.quiz', 'q')
            ->where('ua.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ua.date_attempt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $history = [];
        foreach ($userAnswers as $userAnswer) {
            $quiz = $userAnswer->getQuiz();
            $history[] = [
                'id' => $userAnswer->getId(),
                'quiz' => [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'description' => $quiz->getDescription()
                ],
                'score' => $userAnswer->getTotalScore(),
                'date' => $userAnswer->getDateAttempt()->format('Y-m-d H:i:s'),
                'timestamp' => $userAnswer->getDateAttempt()->getTimestamp()
            ];
        }

        return $history;
    }
}
