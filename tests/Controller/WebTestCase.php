<?php

declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class StatusCodeTest extends WebTestCase
{
    public function testExistingResourceReturns200(): void
    {
        $client = self::createClient();
        $client->request('GET', '/path/that/exists');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testMissingResourceReturns404(): void
    {
        $client = self::createClient();
        $client->request('GET', '/path/that/does-not-exist');
        $this->assertResponseStatusCodeSame(404);
    }
}
