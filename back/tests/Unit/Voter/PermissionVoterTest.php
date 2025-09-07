<?php

namespace App\Tests\Unit\Voter;

use App\Voter\PermissionVoter;
use PHPUnit\Framework\TestCase;

class PermissionVoterTest extends TestCase
{
    private PermissionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new PermissionVoter();
    }

    public function testVoterCreation(): void
    {
        $this->assertInstanceOf(PermissionVoter::class, $this->voter);
    }

    public function testSupportsValidAttributes(): void
    {
        $this->assertTrue($this->voter->supports('create_quiz', null));
        $this->assertTrue($this->voter->supports('CREATE_QUIZ', null));
        $this->assertTrue($this->voter->supports('manage_users', null));
        $this->assertTrue($this->voter->supports('MANAGE_USERS', null));
        $this->assertTrue($this->voter->supports('view_results', null));
        $this->assertTrue($this->voter->supports('VIEW_RESULTS', null));
    }

    public function testSupportsInvalidAttributes(): void
    {
        $this->assertFalse($this->voter->supports('invalid_permission', null));
        $this->assertFalse($this->voter->supports('unknown', null));
        $this->assertFalse($this->voter->supports('', null));
    }

    public function testVoterInheritance(): void
    {
        $reflection = new \ReflectionClass($this->voter);
        $this->assertTrue($reflection->isSubclassOf('Symfony\Component\Security\Core\Authorization\Voter\Voter'));
    }
}

