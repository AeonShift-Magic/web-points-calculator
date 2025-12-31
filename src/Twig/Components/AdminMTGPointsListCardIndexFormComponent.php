<?php

declare(strict_types = 1);

namespace App\Twig\Components;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListCard;
use App\Form\Admin\MTG\AdminMTGPointsListCardIndexFormComponentType;
use App\Repository\MTG\MTGPointsListCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(template: '/admin/mtg/points_list_card/admin_mtg_points_list_card_index_form_component.html.twig')]
final class AdminMTGPointsListCardIndexFormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * @var bool used to determine whether the filter for should be visible cause used or not
     */
    public bool $filtersActive = false;

    #[LiveProp(writable: true, url: true)]
    public ?string $nameEN = null;

    #[LiveProp(url: true)]
    public int $page = 1;

    #[LiveProp(writable: true, url: true)]
    public ?int $pointsList = null;

    public function __construct(
        private MTGPointsListCardRepository $MTGPointsListCardRepository,
        private PaginatorInterface $paginator,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws ORMException
     *
     * @return array<int, MTGPointsListCard>|PaginationInterface<int, mixed>
     */
    public function getSourceCards(): PaginationInterface|array
    {
        $queryBuilder = $this
            ->MTGPointsListCardRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.pointsList', 'l')
            ->addSelect('l')
            ->orderBy('c.updatedAt', 'DESC');

        if ($this->pointsList !== null && is_numeric($this->nameEN)) {
            $queryBuilder
                ->andWhere('c.pointsList = :mtgPointsList')
                ->setParameter('mtgPointsList', $this->entityManager->getReference(MTGPointsList::class, (int)$this->pointsList));
        }

        if ($this->nameEN !== null && $this->nameEN !== '') {
            $queryBuilder
                ->andWhere('c.nameEN LIKE :nameEN')
                ->setParameter('nameEN', '%' . $this->nameEN . '%');
        }

        if ($this->filtersActive === false) {
            return $this->paginator->paginate(
                $queryBuilder,
                $this->page,
                20
            );
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws ORMException
     */
    #[LiveAction]
    public function resetFilters(): void
    {
        $this->nameEN = null;
        $this->pointsList = null;
        $this->filtersActive = false;
        $this->page = 1;

        $this->instantiateForm();
    }

    #[PostHydrate]
    public function updateFilters(): void
    {
        if ($this->nameEN !== null && mb_strlen($this->nameEN) < 2) {
            $this->nameEN = null;
        }

        $this->pointsList = $this->pointsList ?: null;
        $this->filtersActive = (
            $this->pointsList !== null
            || $this->nameEN !== null
        );

        if ($this->filtersActive) {
            $this->page = 1;
        }
    }

    /**
     * @throws ORMException
     * @return FormInterface
     */
    #[Override]
    protected function instantiateForm(): FormInterface
    {
        $formData = new MTGPointsListCard();

        $formData->setNameEN((string)$this->nameEN);
        $formData->setPointsList($this->pointsList ? $this->entityManager->getReference(MTGPointsList::class, $this->pointsList) : null);

        $form = $this->createForm(AdminMTGPointsListCardIndexFormComponentType::class, $formData, [
            'method'          => 'GET',
            'csrf_protection' => false,
        ]);

        $this->formView = $form->createView();

        return $form;
    }
}
