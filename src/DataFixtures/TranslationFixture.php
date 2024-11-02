<?php

namespace App\DataFixtures;

use App\Entity\Translation;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TranslationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $importUser = $this->getReference('user_' . md5('import'));
        foreach ($this->getData() as [$language, $placeholder, $translation]) {
            $user = new Translation();
            $user->setLanguage($language);
            $user->setUser($importUser);
            $user->setPlaceholder($placeholder);
            $user->setTranslation($translation);
            $user->setCreatedAt(new DateTimeImmutable());

            $manager->persist($user);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }

    private function getData(): array
    {
        return [
            ['cn', 'page_title_default', '欢迎'],
            ['cn', 'menu_about', '关于'],
            ['cn', 'menu_events', '活动'],
            ['cn', 'menu_members', '成员'],
            ['cn', 'menu_manage', '管理'],
            ['cn', 'menu_admin', '管理员'],
            ['cn', 'menu_profile_events', '活动'],
            ['cn', 'menu_profile_messages', '信息'],
            ['cn', 'menu_profile_view_profile', '查看简介'],
            ['cn', 'menu_profile_config', '设置和隐私'],
            ['cn', 'menu_profile_logout', '注销'],
            ['cn', 'language_de', '德语'],
            ['cn', 'language_en', '英语'],
            ['cn', 'language_cn', '中文'],
            ['de', 'page_title_default', 'Willkommen'],
            ['de', 'menu_about', 'Über Uns'],
            ['de', 'menu_events', 'Events'],
            ['de', 'menu_members', 'Mitglieder'],
            ['de', 'menu_manage', 'Verwaltung'],
            ['de', 'menu_admin', 'Admin'],
            ['de', 'menu_profile_events', 'Events'],
            ['de', 'menu_profile_messages', 'Nachrichten'],
            ['de', 'menu_profile_view_profile', 'Profil anzeigen'],
            ['de', 'menu_profile_config', 'Einstellungen'],
            ['de', 'menu_profile_logout', 'Abmelden'],
            ['de', 'language_de', 'Deutsch'],
            ['de', 'language_en', 'English'],
            ['de', 'language_cn', 'Chinesisch'],
            ['en', 'page_title_default', 'Welcome'],
            ['en', 'menu_about', 'About'],
            ['en', 'menu_events', 'Events'],
            ['en', 'menu_members', 'Members'],
            ['en', 'menu_manage', 'Manage'],
            ['en', 'menu_admin', 'Admin'],
            ['en', 'menu_profile_events', 'Events'],
            ['en', 'menu_profile_messages', 'Messages'],
            ['en', 'menu_profile_view_profile', 'View profile'],
            ['en', 'menu_profile_config', 'Settings & Privacy'],
            ['en', 'menu_profile_logout', 'Logout'],
            ['en', 'language_de', 'German'],
            ['en', 'language_en', 'English'],
            ['en', 'language_cn', 'Chinese'],
        ];
    }
}
