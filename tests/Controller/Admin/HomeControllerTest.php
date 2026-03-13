<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class HomeControllerTest extends WebTestCase
{
    public function testHomePageLoadsEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en/admin');

        self::assertResponseIsSuccessful();
    }

    public function testHomePageLoadsFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/admin');

        self::assertResponseIsSuccessful();
    }
}
