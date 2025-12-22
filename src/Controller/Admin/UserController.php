<?php

declare(strict_types = 1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\AdminUserPasswordType;
use App\Form\Admin\AdminUserType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/user')]
final class UserController extends AbstractController
{
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'], priority: -10)]
    public function delete(#[MapEntity(id: 'id')] User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(id: 'id')] User $user, TranslatorInterface $translator, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $user->setUpdatedBy($currentUser);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/user/edit.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route(name: 'admin_user_index', methods: ['GET'])]
    public function index(PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $queryBuilder = $entityManager->createQueryBuilder();

        $queryBuilder
            ->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.updatedAt', 'DESC');

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/user/index.html.twig', [
            'users'      => $pagination,
            'page_title' => $translator->trans('admin.nav.users.index.label'),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $user->setUpdatedBy($currentUser);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('global.savesuccess.text'));

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'admin/user/new.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ],
            new Response(null, $form->isSubmitted() && ! $form->isValid() ? 422 : 200)
        );
    }

    #[Route('/edit-password/{id}', name: 'admin_user_edit_password', methods: ['GET', 'POST'])]
    public function passwordEdit(Request $request, #[MapEntity(id: 'id')] User $user, TranslatorInterface $translator, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em): Response
    {
        $user->setPassword('');
        $form = $this->createForm(AdminUserPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isXmlHttpRequest() === false) {
            $user->setPassword($userPasswordHasher->hashPassword($user, $user->getPassword()));
            $em->flush();
            $this->addFlash('success', $translator->trans('admin.user.password.password_updated_success', ['{username}' => $user->getUsername()]));

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }
}
