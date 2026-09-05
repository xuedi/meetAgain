<?php declare(strict_types=1);

namespace Tests\Functional\Controller;

use App\Entity\ChangeProposal;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\Suggestion;
use App\Entity\User;
use App\Enum\ChangeProposalStatus;
use App\Enum\SuggestionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocationSuggestionFlowTest extends WebTestCase
{
    private const string ORGANIZER_EMAIL = 'Admin@example.org';
    private const string MEMBER_EMAIL = 'Adem.Lane@example.org';

    public function testAMemberSuggestionIsApprovedIntoAVenueAfterTheReviewerFixesIt(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitSuggestion($client, 'Suggested Reading Room', '10999');
        $suggestion = $this->latestPendingSuggestion($client);

        $client->loginUser($this->user($client, self::ORGANIZER_EMAIL));
        $crawler = $client->request('GET', '/en/review/suggestions/' . $suggestion->getId());
        $this->assertResponseIsSuccessful();

        // Act
        $form = $crawler->filter('form[action$="/approve"]')->form();
        $form['location[postcode]'] = '10998';
        $client->submit($form);

        // Assert
        $this->assertResponseRedirects();
        $resolved = $this->reloadSuggestion($client, (int) $suggestion->getId());
        self::assertSame(SuggestionStatus::Approved, $resolved->getStatus());

        $created = $this->em($client)->getRepository(Location::class)->find((int) $resolved->getCreatedId());
        self::assertInstanceOf(Location::class, $created);
        self::assertSame('Suggested Reading Room', $created->getName());
        self::assertSame('10998', $created->getPostcode());
        self::assertSame(self::MEMBER_EMAIL, $created->getUser()?->getEmail());
    }

    public function testTheHubListsAPendingSuggestionForTheReviewer(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitSuggestion($client, 'Hub Listed Venue', '10997');
        $suggestion = $this->latestPendingSuggestion($client);

        // Act
        $client->loginUser($this->user($client, self::ORGANIZER_EMAIL));
        $crawler = $client->request('GET', '/en/profile/review');

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href$="/review/suggestions/' . $suggestion->getId() . '"]'));
    }

    public function testAReviewerCanRejectASuggestionWithoutCreatingAVenue(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitSuggestion($client, 'Rejected Venue', '10996');
        $suggestion = $this->latestPendingSuggestion($client);
        $before = $this->venueCount($client);

        $client->loginUser($this->user($client, self::ORGANIZER_EMAIL));
        $crawler = $client->request('GET', '/en/review/suggestions/' . $suggestion->getId());
        $token = (string) $crawler->filter('a[href$="/reject"]')->attr('data-csrf-token');

        // Act
        $client->request('POST', '/en/review/suggestions/' . $suggestion->getId() . '/reject', ['_token' => $token]);

        // Assert
        $this->assertResponseRedirects();
        self::assertSame(SuggestionStatus::Rejected, $this->reloadSuggestion($client, (int) $suggestion->getId())->getStatus());
        self::assertSame($before, $this->venueCount($client));
    }

    public function testTheProposerCanWithdrawTheirOwnPendingSuggestion(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitSuggestion($client, 'Withdrawn Venue', '10995');
        $suggestion = $this->latestPendingSuggestion($client);

        $crawler = $client->request('GET', '/en/locations/suggest');
        $token = (string) $crawler->filter('a[href$="/review/suggestions/' . $suggestion->getId() . '/withdraw"]')->attr('data-csrf-token');

        // Act
        $client->request('POST', '/en/review/suggestions/' . $suggestion->getId() . '/withdraw', ['_token' => $token]);

        // Assert
        $this->assertResponseRedirects();
        self::assertSame(SuggestionStatus::Withdrawn, $this->reloadSuggestion($client, (int) $suggestion->getId())->getStatus());
    }

    public function testAMemberCannotApproveTheirOwnSuggestion(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $this->submitSuggestion($client, 'Self Approved Venue', '10994');
        $suggestion = $this->latestPendingSuggestion($client);

        // Act
        $client->request('POST', '/en/review/suggestions/' . $suggestion->getId() . '/approve');

        // Assert
        $this->assertResponseStatusCodeSame(403);
        self::assertSame(SuggestionStatus::Pending, $this->reloadSuggestion($client, (int) $suggestion->getId())->getStatus());
    }

    public function testAMemberCorrectionBecomesAPendingChangeProposal(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $venue = $this->anyVenue($client);
        $originalStreet = (string) $venue->getStreet();

        $crawler = $client->request('GET', '/en/locations/' . $venue->getId() . '/propose');
        $this->assertResponseIsSuccessful();

        // Act
        $form = $crawler->filter('form[name="location"]')->form();
        $form['location[street]'] = 'Corrected Street 42';
        $client->submit($form);

        // Assert
        $this->assertResponseRedirects();
        $proposals = $this->em($client)->getRepository(ChangeProposal::class)->findBy([
            'targetType' => 'location',
            'targetId' => $venue->getId(),
            'status' => ChangeProposalStatus::Pending,
        ]);
        self::assertCount(1, $proposals);
        self::assertSame('Corrected Street 42', $proposals[0]->getChange('street')->after);
        self::assertSame($originalStreet, $this->reloadVenue($client, (int) $venue->getId())->getStreet());
    }

    public function testTheVenuePageOpensOnAnIntroductionWithEveryGroupVenueInTheNav(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $venue = $this->anyVenue($client);

        // Act
        $crawler = $client->request('GET', '/en/locations');

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href$="/locations/suggest"].panel-block'));
        self::assertCount(1, $crawler->filter('a[href$="/locations/' . $venue->getId() . '/propose"]'));
        self::assertStringContainsString('Fix a venue that exists', $crawler->filter('.column.is-9')->text());
    }

    public function testPickingAVenueInTheNavRendersItsCorrectionForm(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $venue = $this->anyVenue($client);

        // Act
        $crawler = $client->request('GET', '/en/locations/' . $venue->getId() . '/propose');

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('form[name="location"]'));
        self::assertCount(1, $crawler->filter('a.panel-block.is-active[href$="/locations/' . $venue->getId() . '/propose"]'));
    }

    public function testTheSuggestEntryRendersTheNewVenueFormInsideTheSameShell(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));

        // Act
        $crawler = $client->request('GET', '/en/locations/suggest');

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('form[name="location"]'));
        self::assertCount(1, $crawler->filter('a.panel-block.is-active[href$="/locations/suggest"]'));
    }

    public function testTheEventPageLinksToTheVenuePageAndNoLongerOffersAVoteButton(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $event = $this->anyEventWithVenue($client);

        // Act
        $crawler = $client->request('GET', '/en/event/' . $event->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href$="/locations/' . $event->getLocation()?->getId() . '/propose"]'));
        self::assertCount(0, $crawler->filter('a[href*="/voting/poll/create/"]'));
    }

    public function testAnEventWithoutAVenueStillRenders(): void
    {
        // Arrange
        $client = static::createClient();
        $event = $this->anyEventWithVenue($client);
        $event->setLocation(null);
        $this->em($client)->flush();

        // Act
        $client->request('GET', '/en/event/' . $event->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('still being decided', (string) $client->getResponse()->getContent());
    }

    private function submitSuggestion(KernelBrowser $client, string $name, string $postcode): void
    {
        $crawler = $client->request('GET', '/en/locations/suggest');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="location"]')->form();
        $form['location[name]'] = $name;
        $form['location[description]'] = 'Proposed by a member.';
        $form['location[street]'] = 'Vorschlagsweg 1';
        $form['location[city]'] = 'Berlin';
        $form['location[postcode]'] = $postcode;
        $client->submit($form);
        $this->assertResponseRedirects();
    }

    private function latestPendingSuggestion(KernelBrowser $client): Suggestion
    {
        $suggestions = $this->em($client)->getRepository(Suggestion::class)->findBy(
            ['targetType' => 'location', 'status' => SuggestionStatus::Pending],
            ['id' => 'DESC'],
            1,
        );
        if ($suggestions === []) {
            self::fail('No pending venue suggestion was stored');
        }

        return $suggestions[0];
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

    private function anyVenue(KernelBrowser $client): Location
    {
        $venue = $this->em($client)->getRepository(Location::class)->findOneBy([]);
        if (!$venue instanceof Location) {
            self::fail('Required venue fixture missing');
        }

        return $venue;
    }

    private function anyEventWithVenue(KernelBrowser $client): Event
    {
        $events = $this->em($client)->createQuery(
            'SELECT e FROM ' . Event::class . ' e WHERE e.location IS NOT NULL ORDER BY e.id ASC',
        )->setMaxResults(1)->getResult();
        if ($events === []) {
            self::fail('Required event fixture missing');
        }

        return $events[0];
    }

    private function reloadVenue(KernelBrowser $client, int $id): Location
    {
        $em = $this->em($client);
        $em->clear();
        $venue = $em->getRepository(Location::class)->find($id);
        if (!$venue instanceof Location) {
            self::fail('Venue vanished');
        }

        return $venue;
    }

    private function venueCount(KernelBrowser $client): int
    {
        return (int) $this
            ->em($client)
            ->createQuery('SELECT COUNT(l.id) FROM ' . Location::class . ' l')
            ->getSingleScalarResult();
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
