<?php

declare(strict_types = 1);
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

        $this->assertResponseIsSuccessful();
    }

    public function testHomePageLoadsFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/admin');

        $this->assertResponseIsSuccessful();
    }
}
