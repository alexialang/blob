<?php

namespace App\Service;

use App\Entity\User;
use App\Message\Mailer\PasswordResetEmailMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordResetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly MessageBusInterface $bus,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%app.frontend_url%')]
        private readonly string $frontendUrl,
        #[Autowire('%mailer_from%')]
        private readonly string $mailerFrom,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $token = Uuid::v4()->toRfc4122();
        $user->setPasswordResetToken($token);
        $user->setPasswordResetRequestAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->bus->dispatch(new PasswordResetEmailMessage(
            $user->getEmail(),
            $user->getFirstName(),
            $token
        ));
    }

    public function resetPassword(string $token, string $newPassword, string $confirmPassword): bool
    {
        if ($newPassword !== $confirmPassword) {
            return false;
        }

        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);
        if (!$user || $this->tokenExpired($user)) {
            return false;
        }

        $user->setPasswordResetToken(null);
        $user->setPasswordResetRequestAt(null);
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

        $this->em->flush();
        return true;
    }

    private function tokenExpired(User $user): bool
    {
        $requestedAt = $user->getPasswordResetRequestAt();
        return !$requestedAt || $requestedAt->getTimestamp() < (time() - 3600);
    }

    public function sendPasswordResetEmail(string $email, string $firstName, string $token): void
    {
        $resetUrl = rtrim($this->frontendUrl, '/') . '/reset-password/' . $token;

        $emailObject = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'firstName' => $firstName,
                'resetUrl'  => $resetUrl,
            ]);

        $this->mailer->send($emailObject);
    }
}
