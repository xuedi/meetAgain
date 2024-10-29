<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$name, $email, $password, $roles]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($password);
            $user->setRoles($roles);
            $user->setCreatedAt(new DateTimeImmutable());

            $manager->persist($user);
            $manager->flush();

            $this->addReference('user_' . md5((string) $name), $user);
        }
    }

    private function getData(): array
    {
        return [
            ['import', 'system@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_SYSTEM']],
            ['xuedi', 'admin@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER', 'ROLE_MANAGER', 'ROLE_ADMIN']],
            ['yimu', 'yimu.wang.nz@gmail.com', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER', 'ROLE_MANAGER', 'ROLE_ADMIN']],
            ['xiaolong', 'xiaolong@gmail.com', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER', 'ROLE_MANAGER']],
            ['user_a', 'user_a@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER']],
            ['user_b', 'user_b@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER']],
            ['user_c', 'user_c@beijingcode.org', '$2y$13$4OCpKLHN5POFsrAek5RmTu6jAKLyz0xp.czPVLl4yffg91RC9u2fG', ['ROLE_USER']],
        ];
    }
}
