<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$email, $password, $roles]) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($password);
            $user->setRoles($roles);

            $manager->persist($user);
            $manager->flush();

            $this->addReference('user_' . md5($email), $user);
        }
    }

    private function getData(): array
    {
        return [
            ['admin@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER', 'ROLE_MANAGER', 'ROLE_ADMIN']],
            ['manager@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER', 'ROLE_MANAGER']],
            ['user_a@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER']],
            ['user_b@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER']],
            ['user_c@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER']],
        ];
    }
}

