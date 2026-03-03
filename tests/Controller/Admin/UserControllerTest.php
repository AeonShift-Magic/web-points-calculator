<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class UserControllerTest extends WebTestCase
{
    private $ROUTE = '/admin/user';

    public function testUserRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $this->ROUTE);

        $this->assertResponseRedirects();
    }

    public function testUserRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $this->ROUTE);

        $this->assertResponseRedirects();
    }
}
