<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Repository\MTG\MTGPointsListCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGPointsListCardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGPointsListCard extends MTGAbstractCard
{
    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(targetEntity: MTGPointsList::class)]
    private ?MTGPointsList $list = null;

    public function getList(): ?MTGPointsList
    {
        return $this->list;
    }

    public function setList(?MTGPointsList $list): static
    {
        $this->list = $list;

        return $this;
    }
}
