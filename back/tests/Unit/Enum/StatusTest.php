<?php

namespace App\Tests\Unit\Enum;

use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('draft', Status::DRAFT->value);
        $this->assertEquals('published', Status::PUBLISHED->value);
        $this->assertEquals('archived', Status::ARCHIVED->value);
    }

    public function testEnumCases(): void
    {
        $cases = Status::cases();
        $this->assertCount(3, $cases);

        $this->assertInstanceOf(Status::class, Status::DRAFT);
        $this->assertInstanceOf(Status::class, Status::PUBLISHED);
        $this->assertInstanceOf(Status::class, Status::ARCHIVED);
    }

    public function testFromMethod(): void
    {
        $this->assertEquals(Status::DRAFT, Status::from('draft'));
        $this->assertEquals(Status::PUBLISHED, Status::from('published'));
        $this->assertEquals(Status::ARCHIVED, Status::from('archived'));
    }

    public function testTryFromMethod(): void
    {
        $this->assertEquals(Status::DRAFT, Status::tryFrom('draft'));
        $this->assertEquals(Status::PUBLISHED, Status::tryFrom('published'));
        $this->assertEquals(Status::ARCHIVED, Status::tryFrom('archived'));
        $this->assertNull(Status::tryFrom('invalid'));
    }
}
