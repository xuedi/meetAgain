<?php declare(strict_types=1);

namespace Plugin\Glossary\Tests\Functional;

use App\Entity\ChangeProposal;
use App\Entity\User;
use App\Entity\Suggestion;
use App\Enum\ChangeProposalStatus;
use App\Enum\SuggestionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Glossary\Entity\Glossary;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GlossarySuggestionFlowTest extends WebTestCase
{
    private const string MODERATOR_EMAIL = 'Admin@example.org';
    private const string MEMBER_EMAIL = 'Phoenix.Baker@example.org';
    private const string GLOSSARY_HOST = 'dragon.meetagain.local';

    public function testMemberEditCreatesAPendingProposal(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $original = $entry->getDefinitionMap()['en'] ?? null;
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $this->submitCorrection($client, (int) $entry->getId(), 'a member proposal');

        // Assert
        $reloaded = $this->reload($client, (int) $entry->getId());
        self::assertSame($original, $reloaded->getDefinitionMap()['en'] ?? null);
        $proposals = $this->pendingProposals($client, (int) $entry->getId());
        self::assertCount(1, $proposals);
        self::assertSame('a member proposal', $proposals[0]->getChange('definition_en')->after);
    }

    public function testModeratorEditIsWrittenDirectly(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $client->loginUser($this->user($client, self::MODERATOR_EMAIL));

        // Act
        $this->submitEdit($client, (int) $entry->getId(), 'a moderator rewrite');

        // Assert
        $reloaded = $this->reload($client, (int) $entry->getId());
        self::assertSame('a moderator rewrite', $reloaded->getDefinitionMap()['en'] ?? null);
        self::assertCount(0, $this->pendingProposals($client, (int) $entry->getId()));
    }

    public function testModeratorAppliesOneFieldAndDeniesAnother(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $id = (int) $entry->getId();
        $originalPhrase = (string) $entry->getPhrase();

        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitCorrection($client, $id, 'proposal to apply', 'proposed phrase');

        $client->loginUser($this->user($client, self::MODERATOR_EMAIL));
        $proposalId = (int) $this->pendingProposals($client, $id)[0]->getId();
        $crawler = $client->request('GET', '/en/review/proposals/glossary/' . $id, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();
        $token = (string) $crawler->filter('a[href$="/proposal/' . $proposalId . '/apply/definition_en"]')->attr('data-csrf-token');

        // Act
        $client->request('POST', '/en/review/proposal/' . $proposalId . '/apply/definition_en', ['_token' => $token], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects();
        $client->request('POST', '/en/review/proposal/' . $proposalId . '/deny/phrase', ['_token' => $token], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects();

        // Assert
        $reloaded = $this->reload($client, $id);
        self::assertSame('proposal to apply', $reloaded->getDefinitionMap()['en'] ?? null);
        self::assertSame($originalPhrase, $reloaded->getPhrase());
        self::assertSame(ChangeProposalStatus::Approved, $this->proposal($client, $proposalId)->getStatus());
    }

    public function testProposerCanWithdrawAPendingProposal(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $id = (int) $entry->getId();

        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitCorrection($client, $id, 'to be withdrawn');
        $proposalId = (int) $this->pendingProposals($client, $id)[0]->getId();

        $crawler = $client->request('GET', '/en/review/proposals/glossary/' . $id, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();
        $token = (string) $crawler->filter('a[href$="/proposal/' . $proposalId . '/withdraw"]')->attr('data-csrf-token');

        // Act
        $client->request('POST', '/en/review/proposal/' . $proposalId . '/withdraw', ['_token' => $token], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects();
        self::assertSame(ChangeProposalStatus::Withdrawn, $this->proposal($client, $proposalId)->getStatus());
        self::assertCount(0, $this->pendingProposals($client, $id));
    }

    public function testMemberCannotApplyAProposal(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $id = (int) $entry->getId();

        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitCorrection($client, $id, 'a member proposal');
        $proposalId = (int) $this->pendingProposals($client, $id)[0]->getId();

        $crawler = $client->request('GET', '/en/review/proposals/glossary/' . $id, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $token = (string) $crawler->filter('a[href$="/proposal/' . $proposalId . '/withdraw"]')->attr('data-csrf-token');

        // Act
        $client->request('POST', '/en/review/proposal/' . $proposalId . '/apply/definition_en', ['_token' => $token], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        self::assertCount(1, $this->pendingProposals($client, $id));
    }

    public function testApplyWithInvalidCsrfTokenIsRejected(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $id = (int) $entry->getId();

        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitCorrection($client, $id, 'a member proposal');
        $proposalId = (int) $this->pendingProposals($client, $id)[0]->getId();

        $client->loginUser($this->user($client, self::MODERATOR_EMAIL));

        // Act
        $client->request('POST', '/en/review/proposal/' . $proposalId . '/apply/definition_en', ['_token' => 'broken'], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        self::assertCount(1, $this->pendingProposals($client, $id));
    }

    public function testAMemberProposesANewEntryAndTheOrganizerApprovesItIntoTheGlossary(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        $crawler = $client->request('GET', '/en/contribute/glossary/suggest', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="glossary"]')->form();
        $form['glossary[phrase]'] = '半路出家';
        $form['glossary[definition-en]'] = 'A latecomer to a craft. Literally "left home halfway".';
        $client->submit($form, serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseRedirects();

        $suggestion = $this->latestPendingSuggestion($client);
        self::assertNull($this->entryByPhrase($client, '半路出家'), 'a pending suggestion is not yet an entry');

        // Act
        $client->loginUser($this->user($client, self::MODERATOR_EMAIL));
        $review = $client->request('GET', '/en/profile/review', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        self::assertCount(1, $review->filter('a[href$="/review/suggestions/' . $suggestion->getId() . '"]'), 'the reviewer finds it in the hub');

        $crawler = $client->request('GET', '/en/review/suggestions/' . $suggestion->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $client->submit($crawler->filter('form[action$="/approve"]')->form(), serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects();
        self::assertSame(SuggestionStatus::Approved, $this->reloadSuggestion($client, (int) $suggestion->getId())->getStatus());
        self::assertInstanceOf(Glossary::class, $this->entryByPhrase($client, '半路出家'));
    }

    public function testARejectedSuggestionLeavesNoEntryBehind(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        $crawler = $client->request('GET', '/en/contribute/glossary/suggest', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $form = $crawler->filter('form[name="glossary"]')->form();
        $form['glossary[phrase]'] = '画蛇添足';
        $form['glossary[definition-en]'] = 'To ruin something by adding what it did not need.';
        $client->submit($form, serverParameters: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $suggestion = $this->latestPendingSuggestion($client);

        $client->loginUser($this->user($client, self::MODERATOR_EMAIL));
        $crawler = $client->request('GET', '/en/review/suggestions/' . $suggestion->getId(), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $token = (string) $crawler->filter('a[href$="/reject"]')->attr('data-csrf-token');

        // Act
        $client->request('POST', '/en/review/suggestions/' . $suggestion->getId() . '/reject', ['_token' => $token], server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseRedirects();
        self::assertSame(SuggestionStatus::Rejected, $this->reloadSuggestion($client, (int) $suggestion->getId())->getStatus());
        self::assertNull($this->entryByPhrase($client, '画蛇添足'));
    }
    public function testModeratorEditWritesEveryCheckedTag(): void
    {
        // Arrange
        $client = static::createClient();
        $entry = $this->entryWithoutProposals($client);
        $id = (int) $entry->getId();
        $client->loginUser($this->user($client, self::MODERATOR_EMAIL));

        $crawler = $client->request('GET', '/en/glossary/edit/' . $id, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();
        $offered = $crawler->filter('input[name="glossary[itemTags][]"]')->extract(['value']);
        self::assertGreaterThanOrEqual(2, count($offered));
        $checked = [$offered[0], $offered[1]];

        // Act
        $form = $crawler->filter('form[name="glossary"]')->form();
        $form['glossary[itemTags]'] = $checked;
        $client->submit($form);

        // Assert
        $this->assertResponseRedirects();
        $reopened = $client->request('GET', '/en/glossary/edit/' . $id, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $stillChecked = $reopened->filter('input[name="glossary[itemTags][]"][checked]')->extract(['value']);
        self::assertSame($checked, array_values(array_intersect($stillChecked, $checked)));
    }

    private function latestPendingSuggestion(KernelBrowser $client): Suggestion
    {
        $suggestion = $this->em($client)->getRepository(Suggestion::class)->findOneBy(
            ['targetType' => 'glossary', 'status' => SuggestionStatus::Pending],
            ['id' => 'DESC'],
        );
        if (!$suggestion instanceof Suggestion) {
            self::fail('No pending glossary suggestion was stored');
        }

        return $suggestion;
    }

    private function reloadSuggestion(KernelBrowser $client, int $id): Suggestion
    {
        $em = $this->em($client);
        $em->clear();
        $suggestion = $em->getRepository(Suggestion::class)->find($id);
        if (!$suggestion instanceof Suggestion) {
            self::fail('Suggestion vanished');
        }

        return $suggestion;
    }

    private function entryByPhrase(KernelBrowser $client, string $phrase): ?Glossary
    {
        $em = $this->em($client);
        $em->clear();

        return $em->getRepository(Glossary::class)->findOneBy(['phrase' => $phrase]);
    }

    private function submitCorrection(KernelBrowser $client, int $id, string $definition, ?string $phrase = null): void
    {
        $this->submitGlossaryForm($client, '/en/contribute/glossary/' . $id, $definition, $phrase);
    }

    private function submitEdit(KernelBrowser $client, int $id, string $definition, ?string $phrase = null): void
    {
        $this->submitGlossaryForm($client, '/en/glossary/edit/' . $id, $definition, $phrase);
    }

    private function submitGlossaryForm(KernelBrowser $client, string $path, string $definition, ?string $phrase): void
    {
        $crawler = $client->request('GET', $path, server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="glossary"]')->form();
        $form['glossary[definition-en]'] = $definition;
        if ($phrase !== null) {
            $form['glossary[phrase]'] = $phrase;
        }
        $client->submit($form);
        $this->assertResponseRedirects();
    }

    private function entryWithoutProposals(KernelBrowser $client): Glossary
    {
        $entries = $this->em($client)->getRepository(Glossary::class)->findAll();
        foreach ($entries as $entry) {
            if ($this->pendingProposals($client, (int) $entry->getId()) === []) {
                return $entry;
            }
        }

        self::fail('No glossary fixture entry without pending proposals');
    }

    /** @return list<ChangeProposal> */
    private function pendingProposals(KernelBrowser $client, int $targetId): array
    {
        return $this->em($client)->getRepository(ChangeProposal::class)->findBy([
            'targetType' => 'glossary',
            'targetId' => $targetId,
            'status' => ChangeProposalStatus::Pending,
        ]);
    }

    private function proposal(KernelBrowser $client, int $id): ChangeProposal
    {
        $em = $this->em($client);
        $em->clear();
        $proposal = $em->getRepository(ChangeProposal::class)->find($id);
        if (!$proposal instanceof ChangeProposal) {
            self::fail('Change proposal vanished');
        }

        return $proposal;
    }

    private function reload(KernelBrowser $client, int $id): Glossary
    {
        $em = $this->em($client);
        $em->clear();
        $entry = $em->getRepository(Glossary::class)->find($id);
        if (!$entry instanceof Glossary) {
            self::fail('Glossary entry vanished');
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
