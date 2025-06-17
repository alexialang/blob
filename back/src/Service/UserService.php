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
        $confirmationUrl = $this->frontendUrl . '/confirmation-compte//' . $confirmationToken;

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

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        $this->em->flush();

        return $user;
    }
}
