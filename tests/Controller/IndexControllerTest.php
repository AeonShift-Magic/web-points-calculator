<?php

declare(strict_types = 1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class IndexControllerTest extends WebTestCase
{
    private $ROUTE = '/';

    public function testIndexLoadsEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testIndexLoadsFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
