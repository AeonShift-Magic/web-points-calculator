<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGUpdateControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/mtg/update';

    public function testMTGUpdateRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testMTGUpdateRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
