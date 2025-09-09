<?php

namespace App\Tests\Unit\Entity;

use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use PHPUnit\Framework\TestCase;

class RefreshTokenTest extends TestCase
{
    private RefreshToken $refreshToken;

    protected function setUp(): void
    {
        $this->refreshToken = new RefreshToken();
    }

    public function testRefreshTokenMethods(): void
    {
        $refreshToken = 'test_refresh_token';
        $username = 'test_user';
        $valid = new \DateTime('+1 day');

        $this->refreshToken->setRefreshToken($refreshToken);
        $this->refreshToken->setUsername($username);
        $this->refreshToken->setValid($valid);

        $this->assertEquals($refreshToken, $this->refreshToken->getRefreshToken());
        $this->assertEquals($username, $this->refreshToken->getUsername());
        $this->assertEquals($valid, $this->refreshToken->getValid());
    }

    public function testIsValid(): void
    {
        // Token valide
        $this->refreshToken->setValid(new \DateTime('+1 day'));
        $this->assertTrue($this->refreshToken->isValid());

        // Token expiré
        $this->refreshToken->setValid(new \DateTime('-1 day'));
        $this->assertFalse($this->refreshToken->isValid());
    }

    public function testToString(): void
    {
        $refreshToken = 'test_refresh_token';
        $this->refreshToken->setRefreshToken($refreshToken);

        $this->assertEquals($refreshToken, (string) $this->refreshToken);
    }
}
