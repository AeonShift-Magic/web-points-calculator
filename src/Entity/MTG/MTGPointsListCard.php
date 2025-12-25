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
    #[ORM\ManyToOne(targetEntity: MTGPointsList::class, inversedBy: 'MTGPointListCards')]
    private ?MTGPointsList $pointsList = null;

    public function getPointsList(): ?MTGPointsList
    {
        return $this->pointsList;
    }

    public function setPointsList(MTGPointsList|null $list): static
    {
        $this->pointsList = $list;

        return $this;
    }
}
