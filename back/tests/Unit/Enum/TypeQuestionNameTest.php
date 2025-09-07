<?php

namespace App\Tests\Unit\Enum;

use App\Enum\TypeQuestionName;
use PHPUnit\Framework\TestCase;

class TypeQuestionNameTest extends TestCase
{
    public function testTypeQuestionNameCases(): void
    {
        $this->assertEquals('MCQ', TypeQuestionName::MCQ->value);
        $this->assertEquals('multiple_choice', TypeQuestionName::MULTIPLE_CHOICE->value);
        $this->assertEquals('right_order', TypeQuestionName::RIGHT_ORDER->value);
        $this->assertEquals('matching', TypeQuestionName::MATCHING->value);
        $this->assertEquals('find_the_intruder', TypeQuestionName::FIND_THE_INTRUDER->value);
        $this->assertEquals('blind_test', TypeQuestionName::BLIND_TEST->value);
        $this->assertEquals('true_false', TypeQuestionName::TRUE_FALSE->value);
    }
    
    public function testGetNameMethod(): void
    {
        $this->assertEquals('QCM', TypeQuestionName::MCQ->getName());
        $this->assertEquals('Choix multiple', TypeQuestionName::MULTIPLE_CHOICE->getName());
        $this->assertEquals('Remise dans le bon ordre', TypeQuestionName::RIGHT_ORDER->getName());
        $this->assertEquals('Association d\'élément ', TypeQuestionName::MATCHING->getName());
        $this->assertEquals('Trouver l\'intrus', TypeQuestionName::FIND_THE_INTRUDER->getName());
        $this->assertEquals('Blind test', TypeQuestionName::BLIND_TEST->getName());
        $this->assertEquals('Vrai/Faux', TypeQuestionName::TRUE_FALSE->getName());
    }
    
    public function testTypeQuestionNameCasesCount(): void
    {
        $cases = TypeQuestionName::cases();
        $this->assertCount(7, $cases);
    }
}

