<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['username'], message: 'front.users.forms.username.exists')]
#[UniqueEntity(fields: ['email'], message: 'front.users.forms.email.exists')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    use HistoryTrackableEntityTrait {
        HistoryTrackableEntityTrait::__construct as private __traitConstruct;
    }

    /** @const array<string, string> Possible user roles */
    public const array ROLES = [
        'user.roles.user.label'                        => 'ROLE_USER',
        'user.roles.admin.label'                       => 'ROLE_ADMIN',
    ];

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    #[Assert\Email]
    #[Assert\NotNull]
    #[ORM\Column(length: 255)]
    private string $email = '';

    #[Assert\NotNull]
    #[ORM\Column]
    private bool $isVerified = false;

    /**
     * @var string The hashed password
     */
    #[Assert\NotNull]
    #[ORM\Column]
    private string $password = '';

    #[Assert\NotNull]
    #[ORM\Column]
    private DateTimeImmutable $registeredAt;

    /**
     * @var Collection<int, ResetPasswordRequest>
     */
    #[ORM\OneToMany(targetEntity: ResetPasswordRequest::class, mappedBy: 'user')]
    private Collection $resetPasswordRequests {
        get {
            return $this->resetPasswordRequests;
        }
        set(Collection $value) {
            $this->resetPasswordRequests = $value;
        }
    }

    /**
     * @var array<int, string> The user roles
     */
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Choice(['choices' => self::ROLES]),
    ])]
    #[ORM\Column]
    private array $roles = [];

    #[Assert\Length(min: 1, max: 180)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 180)]
    private string $username = '';

    public function __construct()
    {
        $this->__traitConstruct();
        $this->registeredAt = new DateTimeImmutable();
        $this->resetPasswordRequests = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    /**
     * @see UserInterface
     */
    #[Override]
    public function eraseCredentials(): void
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[Override]
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return array<int, string>
     *
     * @see UserInterface
     */
    #[Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getRolesAsString(): string
    {
        return implode(', ', $this->getRoles());
    }

    /**
     * A visual identifier that represents this user.
     *
     * @return non-empty-string
     *
     * @see UserInterface
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        return ! empty($this->username) ? $this->username : ' ';
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function hasRole(string $role): bool
    {
        return ! empty($this->getRoles()) && in_array($role, $this->getRoles(), true);
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setRegisteredAt(DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @param array<int,string>|string $roles
     *
     * @return static
     */
    public function setRoles(array|string $roles): static
    {
        if (is_string($roles)) {
            $this->roles = [$roles];
        } else {
            $this->roles = $roles;
        }

        return $this;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
