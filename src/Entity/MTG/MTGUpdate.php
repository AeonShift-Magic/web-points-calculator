<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\AbstractUpdate;
use App\Repository\MTG\MTGUpdateRepository;
use App\Validator\Constraints\DateRange;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[DateRange]
#[ORM\Entity(repositoryClass: MTGUpdateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGUpdate extends AbstractUpdate
{
    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(targetEntity: MTGPointsList::class, inversedBy: 'MTGUpdates')]
    private ?MTGPointsList $pointsList = null;

    public function getPointsList(): ?MTGPointsList
    {
        return $this->pointsList;
    }

    public function setPointsList(?MTGPointsList $pointsList): static
    {
        $this->pointsList = $pointsList;

        return $this;
    }
}
