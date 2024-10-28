<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@beijingcode.org');
        $admin->setPassword('$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG');
        $admin->setRoles(['ROLE_USER', 'ROLE_MANAGER', 'ROLE_ADMIN']);
        $manager->persist($admin);

        $powerUser = new User();
        $powerUser->setEmail('manager@beijingcode.org');
        $powerUser->setPassword('$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG');
        $powerUser->setRoles(['ROLE_USER', 'ROLE_MANAGER']);
        $manager->persist($powerUser);

        $userA = new User();
        $userA->setEmail('user_a@beijingcode.org');
        $userA->setPassword('$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG');
        $userA->setRoles(['ROLE_USER']);
        $manager->persist($userA);

        $userB = new User();
        $userB->setEmail('user_b@beijingcode.org');
        $userB->setPassword('$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG');
        $userB->setRoles(['ROLE_USER']);
        $manager->persist($userB);

        $userC = new User();
        $userC->setEmail('user_c@beijingcode.org');
        $userC->setPassword('$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG');
        $userC->setRoles(['ROLE_USER']);
        $manager->persist($userC);

        $manager->flush();
    }
}
