<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGPointsListControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/mtg/points-list';

    public function testMTGPointsListRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testMTGPointsListRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
