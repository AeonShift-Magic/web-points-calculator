<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin\MTG;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class MTGSourceDownloadControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/mtg/source-download';

    public function testMTGSourceDownloadRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testMTGSourceDownloadRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
