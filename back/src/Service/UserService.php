<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Enum\Permission;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserService
{
    private readonly string $frontendUrl;

    public function __construct(
        #[Autowire('%mailer_from%')] private readonly string $mailerFrom,
        #[Autowire('%recaptcha_secret_key%')] private readonly string $recaptchaSecretKey,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
        $this->frontendUrl = rtrim($params->get('app.frontend_url'), '/');
    }

    public function list(bool $includeDeleted = false): array
    {
        if ($includeDeleted) {
            return $this->userRepository->findAll();
        }

        return $this->userRepository->findBy(['deletedAt' => null]);
    }

    public function listWithStats(bool $includeDeleted = false, ?User $currentUser = null, int $page = 1, int $limit = 20, ?string $search = null, string $sort = 'id'): array
    {
        if ($currentUser && !$currentUser->isAdmin()) {
            $company = $currentUser->getCompany();
            if ($company) {
                $users = $this->userRepository->findByCompanyWithStats($company->getId(), $includeDeleted);
            } else {
                $users = [$this->userRepository->findWithStats($currentUser->getId())];
            }
        } else {
            $users = $this->userRepository->findAllWithStats($includeDeleted, $page, $limit, $search, $sort);
        }

        $userMap = [];
        foreach ($users as $user) {
            $userId = $user->getId();

            if (!isset($userMap[$userId])) {
                $userMap[$userId] = [
                    'id' => $userId,
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'isActive' => $user->isActive(),
                    'dateRegistration' => $user->getDateRegistration(),
                    'lastAccess' => $user->getLastAccess(),
                    'company' => $user->getCompany() ? [
                        'id' => $user->getCompany()->getId(),
                        'name' => $user->getCompany()->getName(),
                    ] : null,
                    'companyName' => $user->getCompany() ? $user->getCompany()->getName() : null,
                    'groups' => [],
                    'userPermissions' => [],
                ];
            }

            foreach ($user->getGroups() as $group) {
                $groupExists = false;
                foreach ($userMap[$userId]['groups'] as $existingGroup) {
                    if ($existingGroup['id'] === $group->getId()) {
                        $groupExists = true;
                        break;
                    }
                }

                if (!$groupExists) {
                    $userMap[$userId]['groups'][] = [
                        'id' => $group->getId(),
                        'name' => $group->getName(),
                    ];
                }
            }

            foreach ($user->getUserPermissions() as $permission) {
                if (!in_array($permission->getPermission()->value, $userMap[$userId]['userPermissions'])) {
                    $userMap[$userId]['userPermissions'][] = $permission->getPermission()->value;
                }
            }
        }

        return array_values($userMap);
    }

    public function listWithStatsAndPagination(bool $includeDeleted = false, ?User $currentUser = null, int $page = 1, int $limit = 20, ?string $search = null, string $sort = 'id'): array
    {
        $users = $this->listWithStats($includeDeleted, $currentUser, $page, $limit, $search, $sort);

        if ($currentUser && $currentUser->isAdmin()) {
            $total = $this->userRepository->countAllWithStats($includeDeleted, $search);
            $totalPages = (int) ceil($total / $limit);
        } else {
            $total = count($users);
            $totalPages = 1;
        }

        return [
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1,
            ],
        ];
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(array $data): User
    {
        $this->validateUserDataForCreation($data);

        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            throw new \InvalidArgumentException('Cet email est déjà utilisé');
        }

        $this->em->beginTransaction();

        try {
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

            $this->em->commit();

            return $user;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function update(User $user, array $data, ?User $currentUser = null): User
    {
        $this->validateUserData($data);

        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                throw new \InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        $this->em->beginTransaction();

        try {
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
                    $roles = array_filter($roles, fn ($role) => 'ROLE_ADMIN' !== $role);
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
            $this->em->commit();

            return $user;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function anonymizeUser(User $user, ?User $currentUser = null): void
    {
        // Soft delete : marquer comme supprimé sans supprimer physiquement
        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setIsActive(false);

        // Anonymisation des données personnelles
        $user->setEmail('anon_'.$user->getId().'@example.com');
        $user->setFirstName('Utilisateur');
        $user->setLastName('Anonyme');
        $user->setPseudo('Utilisateur_'.substr(hash('sha256', (string) $user->getId()), 0, 8));
        $user->setPassword('');
        $user->setRoles(['ROLE_ANONYMOUS']);

        // Persistance des modifications
        $this->em->persist($user);
        $this->em->flush();
    }

    public function sendEmail(string $email, string $firstName, string $confirmationToken): void
    {
        $confirmationUrl = $this->frontendUrl.'/confirmation-compte/'.$confirmationToken;

        $mail = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('Merci de confirmer votre inscription')
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context([
                'firstName' => $firstName,
                'confirmationUrl' => $confirmationUrl,
            ]);

        $this->mailer->send($mail);
    }

    public function confirmToken(string $token): ?User
    {
        if (empty(trim($token))) {
            throw new \InvalidArgumentException('Le token de confirmation ne peut pas être vide');
        }

        if (strlen($token) < 10) {
            throw new \InvalidArgumentException('Le token de confirmation est invalide');
        }

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
        $this->validateUserData($data);

        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                throw new \InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        if (isset($data['pseudo']) && \is_string($data['pseudo'])) {
            $user->setPseudo($data['pseudo']);
        }
        if (isset($data['firstName']) && \is_string($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName']) && \is_string($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        if (isset($data['avatarShape']) || isset($data['avatarColor'])) {
            $currentAvatar = $user->getAvatar() ? json_decode($user->getAvatar(), true) : [];

            if (isset($data['avatarShape']) && \is_string($data['avatarShape'])) {
                $currentAvatar['shape'] = $data['avatarShape'];
            }
            if (isset($data['avatarColor']) && \is_string($data['avatarColor'])) {
                $currentAvatar['color'] = $data['avatarColor'];
            }

            $user->setAvatar(json_encode($currentAvatar));
        }

        $this->em->flush();

        return $user;
    }

    public function getUsersWithoutCompany(): array
    {
        return $this->userRepository->findBy([
            'company' => null,
            'deletedAt' => null,
            'isActive' => true,
        ]);
    }

    public function getUsersFromOtherCompanies(int $excludeCompanyId): array
    {
        return $this->userRepository->findUsersFromOtherCompanies($excludeCompanyId);
    }

    public function getUserStatistics(User $user): array
    {
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
                ++$totalAttempts;

                if (!isset($quizScores[$quizId]) || $currentScore > $quizScores[$quizId]) {
                    $quizScores[$quizId] = $currentScore;
                }

                $scoreHistory[] = [
                    'date' => $userAnswer->getDateAttempt()->format('Y-m-d H:i:s'),
                    'score' => $currentScore,
                    'quizTitle' => $userAnswer->getQuiz()?->getTitle() ?? 'Quiz inconnu',
                    'quizId' => $quizId,
                    'attemptNumber' => $this->getAttemptNumber($user, $quizId, $userAnswer->getDateAttempt()),
                ];
            }

            if ($userAnswer->getQuiz() && $userAnswer->getQuiz()->getCategory()) {
                $categoryName = $userAnswer->getQuiz()->getCategory()->getName();
                if (!isset($categoryPerformance[$categoryName])) {
                    $categoryPerformance[$categoryName] = ['total' => 0, 'count' => 0];
                }
                $categoryKey = $quizId.'_cat_'.$categoryName;
                if (!isset($uniqueQuizIds[$categoryKey])) {
                    $uniqueQuizIds[$categoryKey] = true;
                    $categoryPerformance[$categoryName]['total'] += ($userAnswer->getTotalScore() ?? 0);
                    ++$categoryPerformance[$categoryName]['count'];
                }
            }
        }

        $totalQuizzesCompleted = count($quizScores);

        usort($scoreHistory, fn ($a, $b) => strtotime((string) $b['date']) - strtotime((string) $a['date']));

        $categoryAverages = [];
        foreach ($categoryPerformance as $category => $data) {
            $categoryAverages[] = [
                'category' => $category,
                'average' => round($data['total'] / $data['count'], 1),
                'count' => $data['count'],
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
            'categoryPerformance' => $categoryAverages,
        ];

        return $stats;
    }

    private function getAttemptNumber(User $user, int $quizId, \DateTime $attemptDate): int
    {
        $attempts = [];
        foreach ($user->getUserAnswers() as $userAnswer) {
            if ($userAnswer->getQuiz()?->getId() === $quizId) {
                $attempts[] = $userAnswer->getDateAttempt();
            }
        }

        usort($attempts, fn ($a, $b) => $a <=> $b);

        foreach ($attempts as $index => $date) {
            if ($date == $attemptDate) {
                return $index + 1;
            }
        }

        return 1;
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
                    'description' => $quiz->getDescription(),
                ],
                'score' => $userAnswer->getTotalScore(),
                'date' => $userAnswer->getDateAttempt()->format('Y-m-d H:i:s'),
                'timestamp' => $userAnswer->getDateAttempt()->getTimestamp(),
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
                    $permissionEnum = Permission::from($permission);
                    $validPermissions[] = $permissionEnum;
                } catch (\ValueError) {
                    continue;
                }
            }

            foreach ($user->getUserPermissions() as $userPermission) {
                $this->em->remove($userPermission);
            }
            $this->em->flush();

            foreach ($validPermissions as $permissionEnum) {
                $userPermission = new UserPermission();
                $userPermission->setUser($user);
                $userPermission->setPermission($permissionEnum);
                $this->em->persist($userPermission);
            }
        }

        $this->em->flush();

        return $user;
    }

    private function validateAndCleanRoles(array $roles): array
    {
        $allowedRoles = ['ROLE_USER', 'ROLE_ADMIN'];

        $unauthorizedRoles = array_diff($roles, $allowedRoles);
        if (!empty($unauthorizedRoles)) {
            throw new \InvalidArgumentException('Rôles non autorisés détectés: '.implode(', ', $unauthorizedRoles));
        }

        $cleanRoles = array_unique($roles);
        sort($cleanRoles);

        if (!in_array('ROLE_USER', $cleanRoles)) {
            $cleanRoles[] = 'ROLE_USER';
        }

        return $cleanRoles;
    }

    public function getUsersByCompany(Company $company): array
    {
        return $this->userRepository->findByCompanyWithStats($company->getId(), false);
    }

    public function verifyCaptcha(string $token, string $action = 'register'): bool
    {
        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->recaptchaSecretKey,
                    'response' => $token,
                ],
            ]);

            $result = $response->toArray();

            if (isset($result['success']) && true === $result['success']) {
                $score = $result['score'] ?? null;
                $actionValid = $result['action'] ?? '';

                // Si score est null, c'est reCAPTCHA v2 (pas de score)
                if (null === $score) {
                    $this->logger->info('reCAPTCHA v2 validé', [
                        'action' => $action,
                    ]);

                    return true;
                }

                // Sinon c'est reCAPTCHA v3, vérifier l'action et le score
                if ($actionValid !== $action) {
                    $this->logger->warning('Action reCAPTCHA invalide', [
                        'expected' => $action,
                        'received' => $actionValid,
                    ]);

                    return false;
                }

                if ($score >= 0.5) {
                    $this->logger->info('reCAPTCHA v3 validé', [
                        'score' => $score,
                        'action' => $action,
                    ]);

                    return true;
                } else {
                    $this->logger->warning('Score reCAPTCHA trop faible', [
                        'score' => $score,
                        'action' => $action,
                    ]);

                    return false;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du captcha: '.$e->getMessage());

            return false;
        }
    }

    private function validateUserDataForCreation(array $data): void
    {
        $constraints = new Assert\Collection([
            'firstName' => [
                new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                new Assert\Length(['min' => 2, 'max' => 100, 'minMessage' => 'Le prénom doit contenir au moins 2 caractères', 'maxMessage' => 'Le prénom ne peut pas dépasser 100 caractères']),
            ],
            'lastName' => [
                new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                new Assert\Length(['min' => 2, 'max' => 100, 'minMessage' => 'Le nom doit contenir au moins 2 caractères', 'maxMessage' => 'Le nom ne peut pas dépasser 100 caractères']),
            ],
            'email' => [
                new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                new Assert\Email(['message' => 'L\'email n\'est pas valide']),
                new Assert\Length(['max' => 180, 'maxMessage' => 'L\'email ne peut pas dépasser 180 caractères']),
            ],
            'password' => [
                new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire']),
                new Assert\Length(['min' => 6, 'max' => 255, 'minMessage' => 'Le mot de passe doit contenir au moins 6 caractères', 'maxMessage' => 'Le mot de passe ne peut pas dépasser 255 caractères']),
            ],
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    /**
     * Valide les données utilisateur pour la mise à jour.
     */
    private function validateUserData(array $data): void
    {
        $constraints = new Assert\Collection([
            'fields' => [
                'firstName' => [
                    new Assert\Optional([
                        new Assert\NotBlank(['message' => 'Le prénom ne peut pas être vide']),
                        new Assert\Length(['min' => 2, 'max' => 100, 'minMessage' => 'Le prénom doit contenir au moins 2 caractères', 'maxMessage' => 'Le prénom ne peut pas dépasser 100 caractères']),
                    ]),
                ],
                'lastName' => [
                    new Assert\Optional([
                        new Assert\NotBlank(['message' => 'Le nom ne peut pas être vide']),
                        new Assert\Length(['min' => 2, 'max' => 100, 'minMessage' => 'Le nom doit contenir au moins 2 caractères', 'maxMessage' => 'Le nom ne peut pas dépasser 100 caractères']),
                    ]),
                ],
                'email' => [
                    new Assert\Optional([
                        new Assert\NotBlank(['message' => 'L\'email ne peut pas être vide']),
                        new Assert\Email(['message' => 'L\'email n\'est pas valide']),
                        new Assert\Length(['max' => 180, 'maxMessage' => 'L\'email ne peut pas dépasser 180 caractères']),
                    ]),
                ],
                'password' => [
                    new Assert\Optional([
                        new Assert\Length(['min' => 6, 'max' => 255, 'minMessage' => 'Le mot de passe doit contenir au moins 6 caractères', 'maxMessage' => 'Le mot de passe ne peut pas dépasser 255 caractères']),
                    ]),
                ],
                'avatar' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 255, 'maxMessage' => 'L\'avatar ne peut pas dépasser 255 caractères']),
                    ]),
                ],
                'avatarShape' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'string', 'message' => 'La forme de l\'avatar doit être une chaîne']),
                        new Assert\Length(['max' => 50, 'maxMessage' => 'La forme de l\'avatar ne peut pas dépasser 50 caractères']),
                    ]),
                ],
                'avatarColor' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'string', 'message' => 'La couleur de l\'avatar doit être une chaîne']),
                        new Assert\Length(['max' => 7, 'maxMessage' => 'La couleur de l\'avatar ne peut pas dépasser 7 caractères']),
                    ]),
                ],
                'pseudo' => [
                    new Assert\Optional([
                        new Assert\Length(['max' => 50, 'maxMessage' => 'Le pseudo ne peut pas dépasser 50 caractères']),
                    ]),
                ],
                'is_admin' => [
                    new Assert\Optional([
                        new Assert\Type(['type' => 'bool', 'message' => 'Le champ is_admin doit être un booléen']),
                    ]),
                ],
            ],
        ]);

        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }

    /**
     * Récupère les utilisateurs actifs pour le multijoueur.
     */
    public function getActiveUsersForMultiplayer(): array
    {
        return $this->userRepository->findActiveUsersForMultiplayer();
    }
}
