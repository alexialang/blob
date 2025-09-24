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

    public function testVoterInheritance(): void
    {
        $reflection = new \ReflectionClass($this->voter);
        $this->assertTrue($reflection->isSubclassOf('Symfony\Component\Security\Core\Authorization\Voter\Voter'));
    }
}
