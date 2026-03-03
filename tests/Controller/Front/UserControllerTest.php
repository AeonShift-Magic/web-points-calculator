<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class UserControllerTest extends WebTestCase
{
    private $ROUTE = '/backend-login';

    public function testUserRedirectsEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testUserRedirectsFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
