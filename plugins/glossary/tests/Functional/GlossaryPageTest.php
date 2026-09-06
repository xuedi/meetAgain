<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Glossary\Entity\Glossary;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GlossaryPageTest extends WebTestCase
{
    private const string MODERATOR_EMAIL = 'Admin@example.org';
    private const string MEMBER_EMAIL = 'Adem.Lane@example.org';
    private const string GLOSSARY_HOST = 'dragon.meetagain.local';
    private const string PLUGINLESS_HOST = 'cinema.meetagain.local';

    public function testListRendersThroughTheSharedItemComponent(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        static::assertStringContainsString('data-item-list="glossary"', (string) $client->getResponse()->getContent());
    }

    public function testSwitcherOffersOnlyListAndTiles(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary');

        // Assert
        static::assertCount(1, $crawler->filter('a[href$="/item/glossary/view/list"]'));
        static::assertCount(1, $crawler->filter('a[href$="/item/glossary/view/tiles"]'));
        static::assertCount(0, $crawler->filter('a[href$="/item/glossary/view/grid"]'));
        static::assertCount(0, $crawler->filter('a[href$="/item/glossary/view/gallery"]'));
    }

    public function testTilesModeIsReachableAndPersists(): void
    {
        // Arrange
        $client = static::createClient();
        $rows = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST])
            ->filter('.item-list tbody tr')->count();

        // Act
        $client->request('GET', '/en/item/glossary/view/tiles', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        static::assertGreaterThan(0, $rows);
        static::assertCount(0, $crawler->filter('.item-list table'));
        static::assertSame($rows, $crawler->filter('.item-list .item-cell')->count());
    }

    public function testDisallowedModeFallsBackToList(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('GET', '/en/item/glossary/view/gallery', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        static::assertCount(1, $crawler->filter('.item-list table'));
    }

    public function testDetailPageIsPublic(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entry($client);

        // Act
        $client->request('GET', '/en/glossary/' . $entry->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        static::assertStringContainsString((string) $entry->getPhrase(), (string) $client->getResponse()->getContent());
    }

    public function testTheHubCarriesTheSectionOnlyWhereThePluginIsOn(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $on = $client->request('GET', '/en/contribute', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $off = $client->request('GET', '/en/contribute', server: ['HTTP_HOST' => self::PLUGINLESS_HOST]);

        // Assert
        self::assertCount(1, $on->filter('.tabs a[href$="/contribute/glossary"]'));
        self::assertCount(0, $off->filter('.tabs a[href$="/contribute/glossary"]'));
        self::assertCount(1, $off->filter('.tabs a[href$="/contribute/location"]'), 'a core section is never gated');
    }

    public function testTheCorrectionFormIsClosedWhereThePluginIsOff(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entry($client);
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $client->request('GET', '/en/contribute/glossary/' . $entry->getId(), server: ['HTTP_HOST' => self::PLUGINLESS_HOST]);

        // Assert
        $this->assertResponseStatusCodeSame(404, 'an inactive plugin closes the form, not only the listing');
    }

    public function testTheHubOffersAnEntryToMembers(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entry($client);
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $crawler = $client->request('GET', '/en/contribute/glossary/' . $entry->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('form[name="glossary"]'));
    }

    public function testTheGlossaryEditPageIsClosedToMembers(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entry($client);
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $client->request('GET', '/en/glossary/edit/' . $entry->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects('/en/profile/my-groups/', message: 'the editor is for organizers of this group');
    }

    private function entry(KernelBrowser $client): Glossary
    {
        $entry = $this->em($client)->getRepository(Glossary::class)->findOneBy([]);
        if (!$entry instanceof Glossary) {
            self::fail('Required glossary fixture entry missing');
        }

        return $entry;
    }

    private function user(KernelBrowser $client, string $email): User
    {
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            self::fail('Required fixture user missing: ' . $email);
        }

        return $user;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
