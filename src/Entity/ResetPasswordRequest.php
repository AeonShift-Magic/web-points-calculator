<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Validator\Constraints as Assert;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    use ResetPasswordRequestTrait;

    #[Assert\NotNull]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(inversedBy: 'resetPasswordRequests')]
    public User $user {
        get {
            return $this->user;
        }
    }

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null {
        get {
            return $this->id;
        }
    }

    public function __construct(User $user, DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->user = $user;
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    #[Override]
    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
