<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\AbstractUpdate;
use App\Repository\MTG\MTGUpdateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MTGUpdateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGUpdate Extends AbstractUpdate
{
    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(targetEntity: MTGPointsList::class)]
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
