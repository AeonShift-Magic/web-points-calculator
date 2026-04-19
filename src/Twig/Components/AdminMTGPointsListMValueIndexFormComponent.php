<?php

declare(strict_types = 1);

namespace App\Twig\Components;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListMValue;
use App\Form\Admin\MTG\AdminMTGPointsListMValueIndexFormComponentType;
use App\Repository\MTG\MTGPointsListMValueRepository;
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

#[AsLiveComponent(template: '/admin/mtg/points_list_mvalue/admin_mtg_points_list_mvalue_index_form_component.html.twig')]
final class AdminMTGPointsListMValueIndexFormComponent extends AbstractController
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
        private MTGPointsListMValueRepository $MTGPointsListMValueRepository,
        private PaginatorInterface $paginator,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws ORMException
     *
     * @return array<int, MTGPointsListMValue>|PaginationInterface<int, mixed>
     */
    public function getpointsListMValues(): PaginationInterface|array
    {
        $queryBuilder = $this
            ->MTGPointsListMValueRepository
            ->createQueryBuilder('c')
            ->leftJoin('c.pointsList', 'l')
            ->addSelect('l');

        if (is_numeric($this->pointsList)) {
            $queryBuilder
                ->andWhere('c.pointsList = :mtgPointsList')
                ->setParameter('mtgPointsList', $this->entityManager->getReference(MTGPointsList::class, $this->pointsList));
        }

        if ($this->nameEN !== null && $this->nameEN !== '') {
            $queryBuilder
                ->andWhere('c.nameEN = :nameEN')
                ->setParameter('nameEN', $this->nameEN);
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
     *
     * @return FormInterface
     */
    #[Override]
    protected function instantiateForm(): FormInterface
    {
        $formData = new MTGPointsListMValue();

        $formData->setNameEN((string)$this->nameEN);
        $formData->setPointsList($this->pointsList !== null ? $this->entityManager->getReference(MTGPointsList::class, $this->pointsList) : null);

        $form = $this->createForm(AdminMTGPointsListMValueIndexFormComponentType::class, $formData, [
            'method'          => 'GET',
            'csrf_protection' => false,
        ]);

        $this->formView = $form->createView();

        return $form;
    }
}
