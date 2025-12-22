<?php

declare(strict_types = 1);

namespace App\Controller\Front;

use App\Entity\Page;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class StaticPagesController extends AbstractController
{
    #[Route('/{slug}-{id}', name: 'front_static_page', requirements: ['slug' => '[0-9a-z\-]+', 'id' => '\d+'], methods: ['GET'], priority: -1)]
    public function index(#[MapEntity(mapping: ['id' => 'id', 'slug' => 'slug'])] Page $page, TranslatorInterface $translator): Response
    {
        $breadcrumb = [
            [
                'link'  => $this->generateUrl('front_home'),
                'text'  => $translator->trans('front.nav.home.label'),
                'title' => $translator->trans('front.nav.home.title'),
            ],
        ];

        $breadcrumb[] =
            [
                'text'  => $page->getTitle(),
                'title' => $page->getTitle(),
            ];

        return $this->render('front/static_pages/page.html.twig', [
            'page'       => $page,
            'breadcrumb' => $breadcrumb,
        ]);
    }
}
