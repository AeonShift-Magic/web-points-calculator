<?php

declare(strict_types = 1);

namespace App\Tests\Controller\API;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @small
 */
final class APIControllerTest extends WebTestCase
{
    public function testIndexReturnsJsonWithPaths(): void
    {
        $client = self::createClient();

        // Appel GET sur /api/
        $client->request('GET', '/api/');

        // Vérifie que la réponse est 200
        $this->assertResponseIsSuccessful();

        // Vérifie que le Content-Type est JSON
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        // Récupère le contenu JSON
        $data = json_decode($client->getResponse()->getContent(), true);

        // Vérifie que la clé "name" est bien présente
        self::assertArrayHasKey('name', $data);
        self::assertSame('AeonShift API', $data['name']);

        // Vérifie que "paths" contient les routes attendues
        self::assertArrayHasKey('paths', $data);
        self::assertArrayHasKey('self', $data['paths']);
        self::assertArrayHasKey('mtg-published-announcements', $data['paths']);
        self::assertArrayHasKey('swagger', $data['paths']);
        self::assertArrayHasKey('swagger-ui', $data['paths']);
    }
}
