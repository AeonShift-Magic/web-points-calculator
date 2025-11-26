<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\CustomBlockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomBlockRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CustomBlock
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    public const array BLOCK_KEYS = [
        'legal.en',
        'legal.fr',
        'footer.en',
        'footer.fr',
        'home1.en',
        'home1.fr',
        'home2.en',
        'home2.fr',
        'home3.en',
        'home3.fr',
        'home4.en',
        'home4.fr',
    ];

    #[Assert\Choice(choices: self::BLOCK_KEYS)]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private string $blockKey = '';

    #[Assert\NotNull]
    #[ORM\Column(type: Types::TEXT)]
    private string $contents = '';

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\Range(min: -1000, max: 1000)]
    #[ORM\Column]
    private int $weight = 0;

    public function __construct()
    {
        $this->__traitConstruct();
    }

    /**
     * @return array<string, string>
     */
    public static function getCustomBlockKeysForForms(): array
    {
        return array_flip(array_combine(self::BLOCK_KEYS, array_map(static fn ($a) => 'customblock.' . $a . '.name', self::BLOCK_KEYS)));
    }

    public function getBlockKey(): string
    {
        return $this->blockKey;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setBlockKey(string $blockKey): static
    {
        $this->blockKey = $blockKey;

        return $this;
    }

    public function setContents(string $contents): static
    {
        $this->contents = $contents;

        return $this;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }
}
