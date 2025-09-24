<?php

namespace App\Enum;

enum TypeQuestionName: string
{
    case MCQ = 'MCQ';

    case MULTIPLE_CHOICE = 'multiple_choice';

    case RIGHT_ORDER = 'right_order';

    case MATCHING = 'matching';

    case FIND_THE_INTRUDER = 'find_the_intruder';

    case BLIND_TEST = 'blind_test';

    case TRUE_FALSE = 'true_false';

    public function getName(): string
    {
        return match ($this) {
            self::MCQ => 'QCM',
            self::MULTIPLE_CHOICE => 'Choix multiple',
            self::RIGHT_ORDER => 'Remise dans le bon ordre',
            self::MATCHING => 'Association d\'élément ',
            self::FIND_THE_INTRUDER => 'Trouver l\'intrus',
            self::BLIND_TEST => 'Blind test',
            self::TRUE_FALSE => 'Vrai/Faux',
        };
    }
}
