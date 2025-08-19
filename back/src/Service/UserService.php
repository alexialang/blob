<?php

namespace App\Service;

use AllowDynamicProperties;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AllowDynamicProperties]
class UserService
{
    private EntityManagerInterface      $em;
    private UserRepository              $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private MessageBusInterface         $bus;
    private MailerInterface             $mailer;
    private HttpClientInterface         $httpClient;
    private string                      $mailerFrom;
    private string                      $frontendUrl;
    private string                      $recaptchaSecretKey;

    public function __construct(
        #[Autowire('%mailer_from%')]  string       $mailerFrom,
        #[Autowire('%recaptcha_secret_key%')] string $recaptchaSecretKey,
        EntityManagerInterface        $em,
        UserRepository                $userRepository,
        UserPasswordHasherInterface   $passwordHasher,
        MessageBusInterface           $bus,
        MailerInterface               $mailer,
        HttpClientInterface           $httpClient,
        ParameterBagInterface         $params,
        ValidatorInterface            $validator

    ) {
        $this->em             = $em;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->bus            = $bus;
        $this->mailer         = $mailer;
        $this->httpClient     = $httpClient;
        $this->mailerFrom     = $mailerFrom;
        $this->recaptchaSecretKey = $recaptchaSecretKey;
        $this->validator      = $validator;

        $this->frontendUrl = rtrim($params->get('app.frontend_url'), '/');
    }

    public function list(bool $includeDeleted = false): array
    {
        if ($includeDeleted) {
            return $this->userRepository->findAll();
        }

        return $this->userRepository->findBy(['deletedAt' => null]);
    }

    public function listWithStats(bool $includeDeleted = false): array
    {
        $users = $this->list($includeDeleted);
        $usersWithStats = [];

        foreach ($users as $user) {
            $stats = $this->getUserStatistics($user);
            $usersWithStats[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'isActive' => $user->isActive(),
                'dateRegistration' => $user->getDateRegistration(),
                'lastAccess' => $user->getLastAccess(),
                'avatar' => $user->getAvatar(),
                'company' => $user->getCompany(),
                'groups' => $user->getGroups(),
                'roles' => $user->getRoles(),
                'permissions' => $user->getUserPermissions(),
                'quizs' => $user->getQuizs(),
                'userAnswers' => $user->getUserAnswers(),
                'badges' => $user->getBadges(),
                'stats' => $stats
            ];
        }

        return $usersWithStats;
    }



    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): User
    {
        $this->validateUserData($data);

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
        $roles = ['ROLE_USER'];
        if ($data['is_admin'] ?? false) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);
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
        $this->validateUserData($data);
        
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
            $roles = $user->getRoles();
            if ($data['is_admin']) {
                if (!in_array('ROLE_ADMIN', $roles)) {
                    $roles[] = 'ROLE_ADMIN';
                }
            } else {
                $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
            }
            $user->setRoles($roles);
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

    public function anonymizeUser(User $user): void
    {
        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setIsActive(false);

        $user->setEmail('anon_' . hash('sha256', $user->getEmail() . time()) . '@example.com');
        $user->setFirstName('Utilisateur');
        $user->setLastName('Anonyme');
        $user->setPseudo('Utilisateur_' . substr(hash('sha256', $user->getId()), 0, 8));
        $user->setPassword('');
        $user->setRoles(['ROLE_ANONYMOUS']);

        $this->em->flush();
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
        static $statsCache = [];
        $userId = $user->getId();
        
        if (isset($statsCache[$userId])) {
            return $statsCache[$userId];
        }
        
        $totalQuizzesCreated = $user->getQuizs()->count();
        
        $uniqueQuizIds = [];
        $quizScores = [];
        $scoreHistory = []; 
        $categoryPerformance = [];
        $totalAttempts = 0;
        
        foreach ($user->getUserAnswers() as $userAnswer) {
            $quizId = $userAnswer->getQuiz()?->getId();
            if ($quizId) {
                $currentScore = $userAnswer->getTotalScore() ?? 0;
                $totalAttempts++;
                
                if (!isset($quizScores[$quizId]) || $currentScore > $quizScores[$quizId]) {
                    $quizScores[$quizId] = $currentScore;
                }
                
                $scoreHistory[] = [
                    'date' => $userAnswer->getDateAttempt()->format('Y-m-d H:i:s'),
                    'score' => $currentScore,
                    'quizTitle' => $userAnswer->getQuiz()?->getTitle() ?? 'Quiz inconnu',
                    'quizId' => $quizId,
                    'attemptNumber' => $this->getAttemptNumber($user, $quizId, $userAnswer->getDateAttempt())
                ];
            }
            
            if ($userAnswer->getQuiz() && $userAnswer->getQuiz()->getCategory()) {
                $categoryName = $userAnswer->getQuiz()->getCategory()->getName();
                if (!isset($categoryPerformance[$categoryName])) {
                    $categoryPerformance[$categoryName] = ['total' => 0, 'count' => 0];
                }
                $categoryKey = $quizId . '_cat_' . $categoryName;
                if (!isset($uniqueQuizIds[$categoryKey])) {
                    $uniqueQuizIds[$categoryKey] = true;
                    $categoryPerformance[$categoryName]['total'] += ($userAnswer->getTotalScore() ?? 0);
                    $categoryPerformance[$categoryName]['count']++;
                }
            }
        }
        
        $totalQuizzesCompleted = count($quizScores);
        
        usort($scoreHistory, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        $categoryAverages = [];
        foreach ($categoryPerformance as $category => $data) {
            $categoryAverages[] = [
                'category' => $category,
                'average' => round($data['total'] / $data['count'], 1),
                'count' => $data['count']
            ];
        }
        
        $totalScore = array_sum($quizScores);
        $averageScore = $totalQuizzesCompleted > 0 ? round($totalScore / $totalQuizzesCompleted, 1) : 0;
        $badgesEarned = $user->getBadges()->count();
        
        $stats = [
            'totalQuizzesCreated' => $totalQuizzesCreated,
            'totalQuizzesCompleted' => $totalQuizzesCompleted,
            'totalAttempts' => $totalAttempts,
            'totalScore' => $totalScore,
            'averageScore' => $averageScore,
            'badgesEarned' => $badgesEarned,
            'memberSince' => $user->getDateRegistration()->format('Y-m-d'),
            'lastAccess' => $user->getLastAccess()?->format('Y-m-d H:i:s'),
            'scoreHistory' => $scoreHistory,
            'categoryPerformance' => $categoryAverages
        ];
        
        $statsCache[$userId] = $stats;
        
        return $stats;
    }
    
    /**
     * Détermine le numéro de tentative pour un quiz donné
     */
    private function getAttemptNumber(User $user, int $quizId, \DateTime $attemptDate): int
    {
        $attempts = [];
        foreach ($user->getUserAnswers() as $userAnswer) {
            if ($userAnswer->getQuiz()?->getId() === $quizId) {
                $attempts[] = $userAnswer->getDateAttempt();
            }
        }
        
        usort($attempts, function($a, $b) {
            return $a <=> $b;
        });
        
        foreach ($attempts as $index => $date) {
            if ($date == $attemptDate) {
                return $index + 1;
            }
        }
        
        return 1;
    }

    public function getLeaderboard(int $limit, User $currentUser): array
    {
        $users = $this->userRepository->findActiveUsersForLeaderboard();

        $leaderboardData = [];
        
        foreach ($users as $user) {
            $uniqueQuizIds = [];
            $quizScores = [];
            
            foreach ($user->getUserAnswers() as $userAnswer) {
                $quizId = $userAnswer->getQuiz()?->getId();
                if ($quizId && !isset($uniqueQuizIds[$quizId])) {
                    $uniqueQuizIds[$quizId] = true;
                    $quizScores[$quizId] = $userAnswer->getTotalScore() ?? 0;
                }
            }
            
            $quizzesCompleted = count($quizScores);
            $totalScore = array_sum($quizScores);
            
            $averageScore = $quizzesCompleted > 0 ? round($totalScore / $quizzesCompleted, 1) : 0;
            $badgesCount = $user->getBadges()->count();
            
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
        
        usort($leaderboardData, function($a, $b) {
            return $b['rankingScore'] - $a['rankingScore'];
        });
        
        foreach ($leaderboardData as $index => &$userData) {
            $userData['position'] = $index + 1;
        }
        
        $currentUserPosition = null;
        $currentUserData = null;
        foreach ($leaderboardData as $userData) {
            if ($userData['isCurrentUser']) {
                $currentUserPosition = $userData['position'];
                $currentUserData = $userData;
                break;
            }
        }
        
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
        $userAnswers = $this->userRepository->findUserGameHistory($user, 50);

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

    public function updateUserRoles(User $user, array $data): User
    {
        if (isset($data['roles']) && \is_array($data['roles'])) {
            $validRoles = $this->validateAndCleanRoles($data['roles']);
            $user->setRoles($validRoles);
        }

        if (isset($data['permissions']) && \is_array($data['permissions'])) {
            $validPermissions = [];
            foreach ($data['permissions'] as $permission) {
                try {
                    $permissionEnum = \App\Enum\Permission::from($permission);
                    $validPermissions[] = $permissionEnum;
                } catch (\ValueError $e) {
                    continue;
                }
            }
            
            foreach ($user->getUserPermissions() as $userPermission) {
                $this->em->remove($userPermission);
            }
            $this->em->flush();

            foreach ($validPermissions as $permissionEnum) {
                $userPermission = new \App\Entity\UserPermission();
                $userPermission->setUser($user);
                $userPermission->setPermission($permissionEnum);
                $this->em->persist($userPermission);
            }
        }

        $this->em->flush();
        return $user;
    }

    /**
     * Valide et nettoie les rôles utilisateur
     * @param array $roles
     * @return array
     * @throws \InvalidArgumentException
     */
    private function validateAndCleanRoles(array $roles): array
    {
        $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];
        
        $unauthorizedRoles = array_diff($roles, $allowedRoles);
        if (!empty($unauthorizedRoles)) {
            throw new \InvalidArgumentException(
                'Rôles non autorisés détectés: ' . implode(', ', $unauthorizedRoles)
            );
        }
        
        $cleanRoles = array_unique($roles);
        sort($cleanRoles);
        
        if (!in_array('ROLE_USER', $cleanRoles)) {
            $cleanRoles[] = 'ROLE_USER';
        }
        
        return $cleanRoles;
    }

    public function verifyCaptcha(string $token): bool
    {
        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->recaptchaSecretKey,
                    'response' => $token,
                ],
            ]);

            $result = $response->toArray();
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateUserData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'firstName' => [
                    new Assert\Optional([
                        new Assert\NotBlank(['message' => 'Le prénom ne peut pas être vide']),
                        new Assert\Length(['max' => 100, 'maxMessage' => 'Le prénom ne peut pas dépasser 100 caractères'])
                    ])
                ],
                'lastName' => [
                    new Assert\Optional([
                        new Assert\NotBlank(['message' => 'Le nom ne peut pas être vide']),
                        new Assert\Length(['max' => 100, 'maxMessage' => 'Le nom ne peut pas dépasser 100 caractères'])
                    ])
                ],
                'email' => [
                    new Assert\Optional([
                        new Assert\Email(['message' => 'L\'email n\'est pas valide']),
                        new Assert\Length(['max' => 180, 'maxMessage' => 'L\'email ne peut pas dépasser 100 caractères'])
                    ])
                ],
                'password' => [
                    new Assert\Optional([
                        new Assert\Length(['min' => 8, 'max' => 255, 'minMessage' => 'Le mot de passe doit contenir au moins 8 caractères'])
                    ])
                ],
                'avatar' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 255, 'maxMessage' => 'L\'avatar ne peut pas dépasser 255 caractères'])
                    ])
                ],
                'is_admin' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'bool', 'message' => 'Le champ is_admin doit être un booléen'])
                    ])
                ]
            ]
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
