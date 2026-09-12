<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Glossary\Entity\Glossary;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferFlowTest extends WebTestCase
{
    private const string ORGANIZER_EMAIL = 'Admin@example.org';
    private const string MEMBER_EMAIL = 'Phoenix.Baker@example.org';
    private const string GLOSSARY_HOST = 'dragon.meetagain.local';

    public function testAnImportedListLandsInTheListItWasImportedInto(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::ORGANIZER_EMAIL));
        $path = sys_get_temp_dir() . '/glossary-import-' . uniqid() . '.txt';
        file_put_contents($path, "#separator:tab\n#columns:Front\tBack\n测试一\tfirst test word\n测试二\tsecond test word\n");

        $crawler = $client->request('GET', '/en/glossary/import', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $upload = $crawler->filter('form[name="glossary_import_upload"]')->form();
        $upload['glossary_import_upload[file]']->upload($path);
        $client->submit($upload, serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects('/en/glossary/import/map');

        $crawler = $client->request('GET', '/en/glossary/import/map', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('测试一', (string) $crawler->filter('table')->text());
        self::assertNull($this->entryByPhrase($client, '测试一'), 'the preview writes nothing');

        // Act
        $mapping = $crawler->filter('button[name="glossary_import_map[import]"]')->form();
        $mapping['glossary_import_map[newTagLabel]'] = 'Imported words';
        $client->submit($mapping, serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects();
        $list = $client->request('GET', (string) $client->getResponse()->headers->get('Location'), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        unlink($path);

        // Assert
        $this->assertResponseIsSuccessful();
        $body = (string) $list->filter('[data-item-list-body]')->text();
        self::assertStringContainsString('测试一', $body);
        self::assertStringContainsString('测试二', $body);
        self::assertStringNotContainsString('你好', $body, 'the redirect filters the list down to the new tag');
        self::assertSame(['en' => 'first test word'], $this->entryByPhrase($client, '测试一')?->getDefinitionMap());
    }

    public function testMembersCannotImport(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $client->request('GET', '/en/glossary/import', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheExportIsAnAnkiReadableFile(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $client->request('GET', '/en/glossary/export', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        self::assertStringStartsWith("#separator:tab\n", $content);
        self::assertStringContainsString("你好\t", $content);
        self::assertStringContainsString('attachment', (string) $client->getResponse()->headers->get('Content-Disposition'));
    }

    private function entryByPhrase(KernelBrowser $client, string $phrase): ?Glossary
    {
        $em = $this->em($client);
        $em->clear();

        return $em->getRepository(Glossary::class)->findOneBy(['phrase' => $phrase]);
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
