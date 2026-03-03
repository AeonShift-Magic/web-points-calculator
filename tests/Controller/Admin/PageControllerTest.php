<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class PageControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/page';

    public function testPageRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $this->ROUTE);

        $this->assertResponseRedirects();
    }

    public function testPageRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $this->ROUTE);

        $this->assertResponseRedirects();
    }
}
