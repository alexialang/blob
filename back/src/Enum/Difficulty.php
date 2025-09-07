<?php

namespace App\Enum;

enum Difficulty: string
{
    case EASY = 'easy';
    case MEDIUM = 'medium';
    case HARD = 'hard';

    public function getLabel(): string
    {
        return match ($this) {
            self::EASY => 'Facile',
            self::MEDIUM => 'Moyen',
            self::HARD => 'Difficile',
        };
    }

    public function getWeight(): int
    {
        return match ($this) {
            self::EASY => 1,
            self::MEDIUM => 2,
            self::HARD => 3,
        };
    }

    public static function fromWeight(float $avgWeight): self
    {
        if ($avgWeight <= 1.3) {
            return self::EASY;
        }
        if ($avgWeight <= 2.3) {
            return self::MEDIUM;
        }

        return self::HARD;
    }

    public static function getAllLabels(): array
    {
        return [
            self::EASY->value => self::EASY->getLabel(),
            self::MEDIUM->value => self::MEDIUM->getLabel(),
            self::HARD->value => self::HARD->getLabel(),
        ];
    }
}
