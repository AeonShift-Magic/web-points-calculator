<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGCardSourceActivityHistoryControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/mtg/card-source-activity-history';

    public function testMTGCardSourceActivityHistoryPageRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testMTGCardSourceActivityHistoryPageRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
