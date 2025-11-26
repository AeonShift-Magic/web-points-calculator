<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find users by role, matching or excluding the given role as string.
     *
     * @param string $role
     * @param string $mode
     *
     * @return User[] Returns an array of User objects
     */
    public function findAllByRole(string $role, string $mode = 'include'): array
    {
        $users = $this->createQueryBuilder('u');

        if($mode === 'exclude') {
            $users = $users->andWhere('u.roles NOT LIKE :role')->setParameter('role', '%"' . $role . '"%');
        } elseif($mode === 'include') {
            $users = $users->andWhere('u.roles LIKE :role')->setParameter('role', '%"' . $role . '"%');
        } elseif($mode === 'single') {
            $users = $users->andWhere('u.roles LIKE :role')->setParameter('role', '["' . $role . '"]');
        }

        /** @var array<User> $result */
        $result = $users->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
