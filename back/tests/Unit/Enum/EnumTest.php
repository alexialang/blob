<?php

namespace App\Tests\Unit\Enum;

use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function testStatusEnum(): void
    {
        $this->assertEquals('draft', Status::DRAFT->value);
        $this->assertEquals('published', Status::PUBLISHED->value);
        $this->assertEquals('archived', Status::ARCHIVED->value);
    }

    public function testStatusEnumCases(): void
    {
        $cases = Status::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(Status::DRAFT, $cases);
        $this->assertContains(Status::PUBLISHED, $cases);
        $this->assertContains(Status::ARCHIVED, $cases);
    }
}

