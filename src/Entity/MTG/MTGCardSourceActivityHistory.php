<?php

declare(strict_types = 1);

namespace App\Entity\MTG;

use App\Entity\AbstractSourceActivityHistory;
use App\Repository\MTG\MTGCardSourceActivityHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MTGCardSourceActivityHistoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MTGCardSourceActivityHistory extends AbstractSourceActivityHistory
{
}
