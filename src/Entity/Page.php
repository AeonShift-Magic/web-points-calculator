<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Page
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    public const array PAGE_ZONES = [
        'header',
        'footer',
    ];

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    #[Assert\NotNull]
    #[ORM\Column(type: Types::TEXT)]
    private string $contents = '';

    #[Assert\Language]
    #[Assert\Length(max: 20)]
    #[Assert\NotNull]
    #[ORM\Column(length: 20)]
    private string $language = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[Assert\Regex('/[0-9a-z\\-]/')]
    #[ORM\Column(length: 255)]
    private string $slug = '';

    #[Assert\Length(max: 255)]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $title = '';

    #[Assert\Range(min: -1000, max: 1000)]
    #[ORM\Column]
    private int $weight = 0;

    #[Assert\Choice(choices: self::PAGE_ZONES)]
    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20)]
    private string $zone = '';

    public function __construct()
    {
        $this->__traitConstruct();
    }

    /**
     * @return array<string, string>
     */
    public static function getPageZonesForForms(): array
    {
        return array_flip(array_combine(self::PAGE_ZONES, array_map(static fn ($a) => 'page.position.' . $a . '.label', self::PAGE_ZONES)));
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): static
    {
        if(empty($this->slug)) {
            $this->slug = new AsciiSlugger()->slug($this->title)->slice(0, 254)->lower()->toString();
        }

        return $this;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getZone(): string
    {
        return $this->zone;
    }

    public function setContents(string $contents): static
    {
        $this->contents = $contents;

        return $this;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function setZone(string $zone): static
    {
        $this->zone = $zone;

        return $this;
    }
}
