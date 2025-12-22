<?php

declare(strict_types = 1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    /**
     * Redirects the "/" url to the correct language
     * The language is guessed by the request preferred language,
     * then falls back to English (en) as default.
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/', name: 'front_index', methods: ['GET'], priority: 100)]
    public function index(Request $request): Response
    {
        // Get the supported locales parameter from services
        /** @var array<int, string> $supportedLocales */
        $supportedLocales = $this->getParameter('app.supported_locales');

        if ($supportedLocales !== []) {
            return $this->redirectToRoute(
                'front_home',
                ['_locale' => $request->getPreferredLanguage($supportedLocales)]
            );
        }

        // By default, return to default locale :(
        return $this->redirectToRoute('front_home');
    }
}
