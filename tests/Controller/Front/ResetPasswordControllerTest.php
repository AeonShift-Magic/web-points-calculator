<?php

declare(strict_types = 1);

namespace App\Tests\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class ResetPasswordControllerTest extends WebTestCase
{
    private $ROUTE = '/reset-password';

    public function testResetPasswordRedirectEN(): void
    {
        $client = self::createClient();
        $client->request('GET', '/en' . $ROUTE);

        $this->assertResponseRedirects();
    }

    public function testResetPasswordRedirectFR(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr' . $ROUTE);

        $this->assertResponseRedirects();
    }
}
