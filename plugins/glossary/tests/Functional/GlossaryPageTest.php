<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Glossary\Entity\Glossary;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GlossaryPageTest extends WebTestCase
{
    private const string MEMBER_EMAIL = 'Adem.Lane@example.org';
    private const string GLOSSARY_HOST = 'dragon.meetagain.local';

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

    public function testTheHubOffersAnEntryToMembers(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entry($client);
        $client->loginUser($this->user($client));

        // Act
        $crawler = $client->request('GET', '/en/contribute/glossary/' . $entry->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('form[name="glossary"]'));
    }

    private function entry(KernelBrowser $client): Glossary
    {
        $entry = $this->em($client)->getRepository(Glossary::class)->findOneBy([]);
        if (!$entry instanceof Glossary) {
            self::fail('Required glossary fixture entry missing');
        }

        return $entry;
    }

    private function user(KernelBrowser $client): User
    {
        $user = $this->em($client)->getRepository(User::class)->findOneBy(['email' => self::MEMBER_EMAIL]);
        if (!$user instanceof User) {
            self::fail('Required fixture user missing: ' . self::MEMBER_EMAIL);
        }

        return $user;
    }

    private function em(KernelBrowser $client): EntityManagerInterface
    {
        return $client->getContainer()->get(EntityManagerInterface::class);
    }
}
