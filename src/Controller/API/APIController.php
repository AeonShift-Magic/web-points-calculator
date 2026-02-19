<?php

declare(strict_types = 1);

namespace App\Controller\API;

use App\Model\AeonShift\PointsList\MTG\MTGPointsListManager;
use App\Model\AeonShift\PointsList\MTG\V1\MTGPublishedAnnouncementsResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(name: 'front_api_')]
final class APIController extends AbstractController
{
    #[OA\Get(
        path: '/api/',
        operationId: 'apiIndex',
        description: 'Lists available API endpoints (absolute URLs) and links to the OpenAPI specification and Swagger UI.',
        summary: 'API index',
        tags: ['API'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Index payload with available paths',
                content: new OA\JsonContent(
                    required: ['name', 'paths'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'AeonShift API'),
                        new OA\Property(
                            property: 'paths',
                            properties: [
                                new OA\Property(property: 'self', type: 'string', example: '/api/'),
                                new OA\Property(property: 'mtg-published-announcements', type: 'string', example: '/api/mtg/published-announcements'),
                                new OA\Property(property: 'swagger', type: 'string', example: '/api/doc.json'),
                                new OA\Property(property: 'swagger-ui', type: 'string', example: '/api/doc'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        return $this->json([
            'name'  => 'AeonShift API',
            'paths' => [
                'self'                        => $urlGenerator->generate('front_api_index', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
                'mtg-published-announcements' => $urlGenerator->generate('front_api_mtg_published_updates', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
                'swagger'                     => $urlGenerator->generate('api.swagger', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
                'swagger-ui'                  => $urlGenerator->generate('api.swagger_ui', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/mtg/published-announcements',
        description: 'Returns all published MTG points list announcements, ordered by starting date, with the merged points list payload.',
        summary: 'Get all published MTG announcements',
        tags: ['MTG Announcements'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Published announcements retrieved successfully',
                content: new Model(type: MTGPublishedAnnouncementsResponse::class)
            ),
        ]
    )]
    #[Route(
        '/mtg/published-announcements',
        name: 'mtg_published_updates',
        methods: ['GET']
    )]
    public function mtgPublishedAnnouncements(
        MTGPointsListManager $MTGPointsListManager
    ): JsonResponse
    {
        $publishedUpdates = $MTGPointsListManager->getAllPointsListsAndUpdatesAsArray();

        return new JsonResponse(
            data: $publishedUpdates,
            status: Response::HTTP_OK
        );
    }
}
