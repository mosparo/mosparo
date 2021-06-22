<?php

namespace Mosparo\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Mosparo\Entity\User;

class UserFixtures extends Fixture
{
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            'mosparo'
        ));
        $user->setRoles([
            'ROLE_SUPER_ADMIN'
        ]);

        $manager->persist($user);
        $manager->flush();
    }
}
