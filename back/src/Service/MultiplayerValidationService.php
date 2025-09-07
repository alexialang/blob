<?php

namespace App\Service;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MultiplayerValidationService
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Valide les données de création de room.
     */
    public function validateRoomData(array $data): void
    {
        $constraints = new Assert\Collection([
            'quizId' => [
                new Assert\NotBlank(['message' => 'L\'ID du quiz est requis']),
                new Assert\Type(['type' => 'integer', 'message' => 'L\'ID du quiz doit être un entier']),
            ],
            'maxPlayers' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'integer', 'message' => 'Le nombre maximum de joueurs doit être un entier']),
                    new Assert\Range(['min' => 2, 'max' => 10, 'notInRangeMessage' => 'Le nombre de joueurs doit être entre 2 et 10']),
                ]),
            ],
            'isTeamMode' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'bool', 'message' => 'Le mode équipe doit être un booléen']),
                ]),
            ],
            'roomName' => [
                new Assert\Optional([
                    new Assert\Length(['max' => 255, 'maxMessage' => 'Le nom de la salle ne peut pas dépasser 255 caractères']),
                ]),
            ],
        ]);

        $this->validate($data, $constraints);
    }

    /**
     * Valide les données de réponse.
     */
    public function validateAnswerData(array $data): void
    {
        $constraints = new Assert\Collection([
            'questionId' => [
                new Assert\NotBlank(['message' => 'L\'ID de la question est requis']),
                new Assert\Type(['type' => 'integer', 'message' => 'L\'ID de la question doit être un entier']),
            ],
            'answer' => [
                new Assert\NotBlank(['message' => 'La réponse est requise']),
                new Assert\AtLeastOneOf([
                    'constraints' => [
                        new Assert\Type(['type' => 'integer', 'message' => 'La réponse doit être un entier']),
                        new Assert\Type(['type' => 'array', 'message' => 'La réponse doit être un tableau']),
                    ],
                    'message' => 'La réponse doit être soit un entier soit un tableau',
                ]),
            ],
            'timeSpent' => [
                new Assert\Optional([
                    new Assert\Type(['type' => 'integer', 'message' => 'Le temps passé doit être un entier']),
                ]),
            ],
        ]);

        $this->validate($data, $constraints);
    }

    /**
     * Valide les données de rejoindre une room.
     */
    public function validateJoinRoomData(array $data): void
    {
        $constraints = new Assert\Collection([
            'teamName' => [
                new Assert\Optional([
                    new Assert\Length(['max' => 100, 'maxMessage' => 'Le nom de l\'équipe ne peut pas dépasser 100 caractères']),
                ]),
            ],
        ]);

        $this->validate($data, $constraints);
    }

    /**
     * Valide les données d'invitation.
     */
    public function validateInvitationData(array $data): void
    {
        $constraints = new Assert\Collection([
            'invitedUserIds' => [
                new Assert\NotBlank(['message' => 'Les utilisateurs à inviter sont requis']),
                new Assert\Type(['type' => 'array', 'message' => 'Les utilisateurs à inviter doivent être un tableau']),
            ],
        ]);

        $this->validate($data, $constraints);
    }

    /**
     * Méthode privée pour effectuer la validation.
     */
    private function validate(array $data, Assert\Collection $constraints): void
    {
        $errors = $this->validator->validate($data, $constraints);
        if (count($errors) > 0) {
            throw new ValidationFailedException($constraints, $errors);
        }
    }
}
