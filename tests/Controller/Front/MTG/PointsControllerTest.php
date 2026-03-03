<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Front\MTG;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class PointsControllerTest extends WebTestCase
{
    private $ROUTE = '/mtg';

    public function testPointsPageLoadsEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testPointsPageLoadsFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
