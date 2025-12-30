<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGUpdate;
use App\Entity\User;
use App\Form\Admin\MTG\AdminMTGUpdateType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/mtg/update')]
final class MTGUpdateController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_mtg_update_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] MTGUpdate $update, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $entityManager->remove($update);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('global.deletesuccess.text'));

        return $this->redirectToRoute('admin_mtg_update_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_mtg_update_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] MTGUpdate $update, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(AdminMTGUpdateType::class, $update);
        $form->handleRequest($request);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $update->setUpdatedBy($currentUser);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_update_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/update/edit.html.twig',
            [
                'update' => $update,
                'form'   => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route(name: 'admin_mtg_update_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager
            ->createQueryBuilder()
            ->select('u')
            ->from(MTGUpdate::class, 'u')
            ->orderBy('u.startingAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/mtg/update/index.html.twig', [
            'updates' => $pagination,
        ]);
    }

    #[Route('/new', name: 'admin_mtg_update_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $update = new MTGUpdate();
        $form = $this->createForm(AdminMTGUpdateType::class, $update);
        $form->handleRequest($request);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $update->setCreatedBy($currentUser);
            $update->setUpdatedBy($currentUser);
            $entityManager->persist($update);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_update_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/update/new.html.twig',
            [
                'update' => $update,
                'form'   => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route('/{id}/publish', name: 'admin_mtg_update_publish', methods: ['GET'])]
    public function publish(#[MapEntity(id: 'id')] MTGUpdate $update, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $update->setIsPublic(true);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('admin.mtg.update.published'));

        return $this->redirectToRoute('admin_mtg_update_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'admin_mtg_update_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] MTGUpdate $update): Response
    {
        return $this->render('admin/mtg/update/show.html.twig', [
            'update' => $update,
        ]);
    }

    #[Route('/{id}/unpublish', name: 'admin_mtg_update_unpublish', methods: ['GET'])]
    public function unpublish(#[MapEntity(id: 'id')] MTGUpdate $update, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $update->setIsPublic(false);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('admin.mtg.update.unpublished'));

        return $this->redirectToRoute('admin_mtg_update_index', [], Response::HTTP_SEE_OTHER);
    }
}
