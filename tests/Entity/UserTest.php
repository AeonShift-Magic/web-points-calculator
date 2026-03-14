<?php

declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class UserTest extends TestCase
{
    public function testCreatedAt(): void
    {
        $user = new User();
        $date = new DateTime();

        $user->createdAt = $date;

        self::assertSame($date, $user->createdAt);
    }

    public function testEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';

        $user->setEmail($email);

        self::assertSame($email, $user->getEmail());
    }

    public function testRoles(): void
    {
        $user = new User();

        // Default role should be ROLE_USER
        self::assertContains('ROLE_USER', $user->getRoles());

        // Test setting roles
        $roles = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
        $user->setRoles($roles);

        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertContains('ROLE_SUPER_ADMIN', $user->getRoles());
        // Should still contain ROLE_USER
        self::assertContains('ROLE_USER', $user->getRoles());
    }

    public function testToString(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $identifier = 'DocFX';

        $user->setEmail($email);
        $user->setUsername($identifier);

        self::assertSame($identifier, (string)$user);
    }

    public function testUpdatedAt(): void
    {
        $user = new User();
        $date = new DateTime();

        $user->updatedAt = $date;

        self::assertSame($date, $user->updatedAt);
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $email = 'test@example.com';
        $identifier = 'DocFX';

        $user->setEmail($email);
        $user->setUsername($identifier);

        self::assertSame($email, $user->getEmail());
        self::assertSame($identifier, $user->getUsername());
        self::assertSame($identifier, $user->getUserIdentifier());
    }
}
