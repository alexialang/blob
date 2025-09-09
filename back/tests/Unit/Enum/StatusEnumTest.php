<?php

namespace App\Tests\Unit\Enum;

use App\Enum\Status;
use PHPUnit\Framework\TestCase;

class StatusEnumTest extends TestCase
{
    public function testStatusEnumValues(): void
    {
        $cases = Status::cases();
        $this->assertCount(3, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('draft', $values);
        $this->assertContains('published', $values);
        $this->assertContains('archived', $values);
    }

    public function testStatusEnumHasMethods(): void
    {
        $this->assertTrue(method_exists(Status::class, 'cases'));
        $this->assertTrue(method_exists(Status::class, 'from'));
        $this->assertTrue(method_exists(Status::class, 'tryFrom'));
    }
}
