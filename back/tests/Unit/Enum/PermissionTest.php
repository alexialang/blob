<?php

namespace App\Tests\Unit\Enum;

use App\Enum\Permission;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    public function testPermissionCases(): void
    {
        $this->assertEquals('CREATE_QUIZ', Permission::CREATE_QUIZ->value);
        $this->assertEquals('MANAGE_USERS', Permission::MANAGE_USERS->value);
        $this->assertEquals('VIEW_RESULTS', Permission::VIEW_RESULTS->value);
    }

    public function testPermissionCasesCount(): void
    {
        $cases = Permission::cases();
        $this->assertCount(3, $cases);
    }

    public function testTryFromValidValue(): void
    {
        $this->assertSame(Permission::CREATE_QUIZ, Permission::tryFrom('CREATE_QUIZ'));
        $this->assertSame(Permission::MANAGE_USERS, Permission::tryFrom('MANAGE_USERS'));
        $this->assertSame(Permission::VIEW_RESULTS, Permission::tryFrom('VIEW_RESULTS'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(Permission::tryFrom('INVALID_PERMISSION'));
    }
}
