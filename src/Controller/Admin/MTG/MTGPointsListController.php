<?php

declare(strict_types = 1);

namespace App\Controller\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\User;
use App\Form\Admin\MTG\AdminMTGPointsListImportType;
use App\Form\Admin\MTG\AdminMTGPointsListType;
use App\Model\AeonShift\PointsList\PointsListModelInterface;
use App\Repository\MTG\MTGSourceCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/mtg/points-list')]
final class MTGPointsListController extends AbstractController
{
    #[Route('/{id}/cards', name: 'admin_mtg_points_list_card', methods: ['GET', 'POST'])]
    public function cards(#[MapEntity(id: 'id')] MTGPointsList $pointsList): Response
    {
        return $this->render(
            'admin/mtg/points_list/cards.html.twig',
            [
                'points_list' => $pointsList,
            ]
        );
    }

    #[Route('/{id}/delete', name: 'admin_mtg_points_list_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] MTGPointsList $pointsList, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $entityManager->remove($pointsList);
        $entityManager->flush();
        $this->addFlash('success', $translator->trans('global.deletesuccess.text'));

        return $this->redirectToRoute('admin_mtg_points_list_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_mtg_points_list_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] MTGPointsList $pointsList, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(AdminMTGPointsListType::class, $pointsList);
        $form->handleRequest($request);
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $pointsList->setUpdatedBy($currentUser);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_points_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/points_list/edit.html.twig',
            [
                'points_list' => $pointsList,
                'form'        => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    /**
     * Import a Points List cards.
     *
     * @param MTGPointsList $MTGPointsList
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @return Response
     */
    #[Route('/{id}/import', name: 'admin_mtg_points_list_import', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function import(
        #[MapEntity(id: 'id')]
        MTGPointsList $MTGPointsList,
        Request $request,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        MTGSourceCardRepository $MTGSourceCardRepository,
        Security $security
    ): Response
    {
        $pointsListModelClass = $MTGPointsList->getRulesModel();

        if (! class_exists($pointsListModelClass)) {
            throw $this->createNotFoundException('Given Points List doesn\'have a valid Rules Model.');
        }

        /** @var PointsListModelInterface $pointsListModel */
        $pointsListModel = new $pointsListModelClass(
            $entityManager,
            $translator,
            $MTGSourceCardRepository,
            $security
        );

        $form = $this->createForm(AdminMTGPointsListImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid() && $form->get('csv_file')->getData() instanceof UploadedFile) {
                try {
                    $uploadedFile = $form->get('csv_file')->getData();
                    $fileContents = file_get_contents($uploadedFile->getPathname());

                    if ($fileContents === false) {
                        $this->addFlash('error', $translator->trans('admin.form.mtg.pointslist.import.file.unreadable'));
                    } else {
                        $result = $pointsListModel->processCSVString($fileContents, $MTGPointsList);

                        if ($result['status'] === 'success') {
                            $this->addFlash('success', $result['message']);

                            return $this->redirectToRoute('admin_mtg_points_list_show', ['id' => $MTGPointsList->id]);
                        }

                        $this->addFlash('error', $result['message']);
                    }
                } catch (RuntimeException $e) {
                    return $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }
            } else {
                $this->addFlash('error', $translator->trans('admin.form.mtg.pointslist.import.file.unreadable'));
            }
        }

        return $this->render(
            'admin/mtg/points_list/import.html.twig',
            [
                'points_list' => $MTGPointsList,
                'form'        => $form->createView(),
            ]
        );
    }

    #[Route(name: 'admin_mtg_points_list_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(MTGPointsList::class, 'p')
            ->orderBy('p.validityStartingAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/mtg/points_list/index.html.twig', [
            'points_lists' => $pagination,
        ]);
    }

    #[Route('/new', name: 'admin_mtg_points_list_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $pointsList = new MTGPointsList();
        $form = $this->createForm(AdminMTGPointsListType::class, $pointsList);
        $form->handleRequest($request);
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $pointsList->setCreatedBy($currentUser);
            $pointsList->setUpdatedBy($currentUser);
            $entityManager->persist($pointsList);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_mtg_points_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/mtg/points_list/new.html.twig',
            [
                'points_list' => $pointsList,
                'form'        => $form,
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    /**
     * Streamed response to download a CSV file of a given points list, provided the current user has access to it.
     *
     * @param MTGPointsList $MTGPointsList
     * @param EntityManagerInterface $entityManager
     * @param MTGSourceCardRepository $MTGSourceCardRepository
     * @param Security $security
     * @param TranslatorInterface $translator
     *
     * @return Response
     */
    #[Route('/{id}/export', name: 'admin_mtg_points_list_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function pointsListExportContents(
        #[MapEntity(id: 'id')]
        MTGPointsList $MTGPointsList,
        EntityManagerInterface $entityManager,
        MTGSourceCardRepository $MTGSourceCardRepository,
        Security $security,
        TranslatorInterface $translator
    ): Response
    {
        $pointsListModelClass = $MTGPointsList->getRulesModel();

        if (! class_exists($pointsListModelClass)) {
            throw $this->createNotFoundException('Given Points List doesn\'have a valid Rules Model.');
        }

        /** @var PointsListModelInterface $pointsListModel */
        $pointsListModel = new $pointsListModelClass(
            $entityManager,
            $translator,
            $MTGSourceCardRepository,
            $security
        );

        return $pointsListModel->generateCSVResponseForList($MTGPointsList);
    }

    #[Route('/{id}', name: 'admin_mtg_points_list_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] MTGPointsList $pointsList): Response
    {
        return $this->render('admin/mtg/points_list/show.html.twig', [
            'points_list' => $pointsList,
        ]);
    }
}
