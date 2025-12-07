<?php

declare(strict_types = 1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Trait HistoryTrackableEntity.
 *
 * Allows historization of any content, generically
 */
#[ORM\HasLifecycleCallbacks]
trait HistoryTrackableEntityTrait
{
    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    /**
     * @var User|null
     */
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'datetime')]
    private DateTime $updatedAt {
        get {
            return $this->updatedAt;
        }
        set {
            $this->updatedAt = $value;
        }
    }

    /**
     * @var User|null
     */
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne]
    private ?User $updatedBy = null;

    /**
     * Tricky: traits aren't supposed to have constructors, use this with:
     *
     * use HistoryTrackableEntityTrait {
     *     HistoryTrackableEntityTrait::__construct as private __traitConstruct;
     * }
     *
     * then:
     *
     * public function __construct()
     * {
     *      $this->__traitConstruct();
     * }
     */
    protected function __construct()
    {
        $this->setCreatedAt(new DateTime());
        $this->updatedAt = new DateTime();
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtToCurrent(): void
    {
        $this->createdAt = new DateTime();
    }

    public function setCreatedBy(?User $createdBy = null): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function setCreatedByToCurrent(Security $security): void
    {
        /** @var ?User $currentUser */
        $currentUser = $security->getUser();

        if($currentUser instanceof User) {
            $this->setCreatedBy($currentUser);
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedAtToCurrent(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function setUpdatedBy(?User $updatedBy = null): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function setUpdatedByToCurrent(Security $security): void
    {
        /** @var User $currentUser */
        $currentUser = $security->getUser();
        $this->setUpdatedBy($currentUser);
    }
}
