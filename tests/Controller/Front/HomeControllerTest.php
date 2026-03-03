<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Front;

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
        $client->request('GET', '/en/home');

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageLoadsFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/home');

        $this->assertResponseIsSuccessful();
    }
}
