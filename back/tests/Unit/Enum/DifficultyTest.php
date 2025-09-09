<?php

namespace App\Tests\Unit\Enum;

use App\Enum\Difficulty;
use PHPUnit\Framework\TestCase;

class DifficultyTest extends TestCase
{
    public function testDifficultyCases(): void
    {
        $this->assertEquals('easy', Difficulty::EASY->value);
        $this->assertEquals('medium', Difficulty::MEDIUM->value);
        $this->assertEquals('hard', Difficulty::HARD->value);
    }

    public function testGetLabelMethod(): void
    {
        $this->assertEquals('Facile', Difficulty::EASY->getLabel());
        $this->assertEquals('Moyen', Difficulty::MEDIUM->getLabel());
        $this->assertEquals('Difficile', Difficulty::HARD->getLabel());
    }

    public function testGetWeightMethod(): void
    {
        $this->assertEquals(1, Difficulty::EASY->getWeight());
        $this->assertEquals(2, Difficulty::MEDIUM->getWeight());
        $this->assertEquals(3, Difficulty::HARD->getWeight());
    }

    public function testFromWeightMethod(): void
    {
        $this->assertSame(Difficulty::EASY, Difficulty::fromWeight(1.0));
        $this->assertSame(Difficulty::EASY, Difficulty::fromWeight(1.3));
        $this->assertSame(Difficulty::MEDIUM, Difficulty::fromWeight(1.5));
        $this->assertSame(Difficulty::MEDIUM, Difficulty::fromWeight(2.3));
        $this->assertSame(Difficulty::HARD, Difficulty::fromWeight(2.5));
        $this->assertSame(Difficulty::HARD, Difficulty::fromWeight(3.0));
    }

    public function testGetAllLabelsMethod(): void
    {
        $expectedLabels = [
            'easy' => 'Facile',
            'medium' => 'Moyen',
            'hard' => 'Difficile',
        ];

        $this->assertEquals($expectedLabels, Difficulty::getAllLabels());
    }

    public function testDifficultyCasesCount(): void
    {
        $cases = Difficulty::cases();
        $this->assertCount(3, $cases);
    }
}
