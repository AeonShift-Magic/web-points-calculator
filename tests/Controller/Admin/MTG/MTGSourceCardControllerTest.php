<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGSourceCardControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/mtg/source-card';

    public function testMTGSourceCardRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testMTGSourceCardRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
