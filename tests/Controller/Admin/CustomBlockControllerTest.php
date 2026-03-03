<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class CustomBlockControllerTest extends WebTestCase
{
    public function testCustomBlockRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en/admin/custom-block');

        $this->assertResponseRedirects();
    }

    public function testCustomBlockRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/admin/custom-block');

        $this->assertResponseRedirects();
    }
}
