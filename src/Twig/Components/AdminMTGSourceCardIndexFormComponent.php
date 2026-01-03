<?php

declare(strict_types = 1);

namespace App\Twig\Components;

use App\Entity\MTG\MTGSourceCard;
use App\Form\Admin\MTG\AdminMTGSourceCardIndexFormComponentType;
use App\Repository\MTG\MTGSourceCardRepository;
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

#[AsLiveComponent(template: '/admin/mtg/source_card/admin_mtg_source_card_index_form_component.html.twig')]
final class AdminMTGSourceCardIndexFormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * @var bool used to determine whether the filter should be visible cause used or not
     */
    public bool $filtersActive = false;

    /**
     * null => no filter, true => eligible only, false => not eligible only.
     */
    #[LiveProp(writable: true, url: true)]
    public ?bool $isCommandZoneEligible = null;

    #[LiveProp(writable: true, url: true)]
    public ?string $nameEN = null;

    #[LiveProp(url: true)]
    public int $page = 1;

    public function __construct(
        private MTGSourceCardRepository $MTGSourceCardRepository,
        private PaginatorInterface $paginator,
    )
    {
    }

    /**
     * @return array<int, MTGSourceCard>|PaginationInterface<int, mixed>
     */
    public function getSourceCards(): PaginationInterface|array
    {
        $queryBuilder = $this
            ->MTGSourceCardRepository
            ->createQueryBuilder('c')
            ->orderBy('c.updatedAt', 'DESC');

        if ($this->nameEN !== null && $this->nameEN !== '') {
            $queryBuilder
                ->andWhere('c.nameEN LIKE :nameEN')
                ->setParameter('nameEN', '%' . $this->nameEN . '%');
        }

        if ($this->isCommandZoneEligible !== null) {
            $queryBuilder
                ->andWhere('c.isCommandZoneEligible = :isCommandZoneEligible')
                ->setParameter('isCommandZoneEligible', $this->isCommandZoneEligible);
        }

        if ($this->filtersActive === false) {
            return $this->paginator->paginate(
                $queryBuilder,
                $this->page,
                20
            );
        }

        $queryBuilder->setMaxResults(100);

        return $queryBuilder->getQuery()->getResult();
    }

    #[LiveAction]
    public function resetFilters(): void
    {
        $this->nameEN = null;
        $this->isCommandZoneEligible = null;
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
            $this->nameEN !== null
            || $this->isCommandZoneEligible !== null
        );

        if ($this->filtersActive) {
            $this->page = 1;
        }
    }

    #[Override]
    protected function instantiateForm(): FormInterface
    {
        $formData = new MTGSourceCard();

        $formData->setNameEN((string)$this->nameEN);
        $formData->setIsCommandZoneEligible($this->isCommandZoneEligible ?? false);

        $form = $this->createForm(AdminMTGSourceCardIndexFormComponentType::class, $formData, [
            'method'          => 'GET',
            'csrf_protection' => false,
        ]);

        $this->formView = $form->createView();

        return $form;
    }
}
