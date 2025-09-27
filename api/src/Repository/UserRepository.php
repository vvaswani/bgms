<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOrCreateByEmail(string $email, ?string $name = null): User
    {
        $user = $this->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            if ($name) {
                $user->setName($name);
            }
            $this->_em->persist($user);
            $this->_em->flush();
        } elseif ($name && $user->getName() !== $name) {
            $user->setName($name);
            $this->_em->flush();
        }
        return $user;
    }
}
