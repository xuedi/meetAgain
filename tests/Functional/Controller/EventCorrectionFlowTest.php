<?php declare(strict_types=1);

namespace Tests\Functional\Controller;

use App\Entity\ChangeProposal;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\ChangeProposalStatus;
use App\Review\ChangeProposalService;
use App\Review\EventChangeTarget;
use App\Review\FieldChange;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventCorrectionFlowTest extends WebTestCase
{
    private const string ORGANIZER_EMAIL = 'Admin@example.org';
    private const string MEMBER_EMAIL = 'Adem.Lane@example.org';

    public function testTheFormOffersOneFieldPerTranslatedPropertyAndLocale(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $event = $this->multilingualEvent($client);

        // Act
        $crawler = $client->request('GET', '/en/contribute/event/' . $event->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $locales = $this->localesOf($event);
        foreach ($locales as $locale) {
            foreach (['title', 'teaser', 'description'] as $property) {
                self::assertCount(
                    1,
                    $crawler->filter('[name="event_correction[' . $property . '_' . $locale . ']"]'),
                    $property . '_' . $locale . ' is missing from the form',
                );
            }
        }
    }

    public function testACorrectionBecomesAPendingProposalWithoutTouchingTheEvent(): void
    {
        // Arrange
        $client = static::createClient();
        $client->loginUser($this->user($client, self::MEMBER_EMAIL));
        $event = $this->multilingualEvent($client);
        $eventId = (int) $event->getId();
        $original = $event->getTitle('en');
        $crawler = $client->request('GET', '/en/contribute/event/' . $eventId);

        // Act
        $form = $crawler->filter('form[name="event_correction"]')->form();
        $form['event_correction[title_en]'] = 'Corrected Event Title';
        $client->submit($form);

        // Assert
        $this->assertResponseRedirects();
        $proposals = $this->em($client)->getRepository(ChangeProposal::class)->findBy([
            'targetType' => EventChangeTarget::TARGET_TYPE,
            'targetId' => $eventId,
            'status' => ChangeProposalStatus::Pending,
        ]);
        self::assertCount(1, $proposals);
        self::assertSame('Corrected Event Title', $proposals[0]->getChange('title_en')->after);
        self::assertSame($original, $this->reloadEvent($client, $eventId)->getTitle('en'), 'the event itself is untouched');
    }

    public function testApplyingTheProposalRewritesOnlyThatLocale(): void
    {
        // Arrange
        $client = static::createClient();
        $member = $this->user($client, self::MEMBER_EMAIL);
        $client->loginUser($member);
        $event = $this->multilingualEvent($client);
        $eventId = (int) $event->getId();
        $otherLocale = $this->otherLocale($event);
        $otherTitleBefore = $event->getTitle($otherLocale);

        $service = static::getContainer()->get(ChangeProposalService::class);
        $service->propose(EventChangeTarget::TARGET_TYPE, $eventId, $member, [
            new FieldChange('title_en', $event->getTitle('en'), 'Applied English Title'),
        ]);
        $proposal = $this->em($client)->getRepository(ChangeProposal::class)->findOneBy([
            'targetType' => EventChangeTarget::TARGET_TYPE,
            'targetId' => $eventId,
            'status' => ChangeProposalStatus::Pending,
        ]);
        self::assertInstanceOf(ChangeProposal::class, $proposal);

        // Act
        $client->loginUser($this->user($client, self::ORGANIZER_EMAIL));
        $service->applyField($proposal, 'title_en', $this->user($client, self::ORGANIZER_EMAIL));

        // Assert
        $reloaded = $this->reloadEvent($client, $eventId);
        self::assertSame('Applied English Title', $reloaded->getTitle('en'));
        self::assertSame($otherTitleBefore, $reloaded->getTitle($otherLocale), 'the other locale is untouched');
    }

    public function testAnUnknownFieldIsRefusedByValidation(): void
    {
        // Arrange
        $client = static::createClient();
        $event = $this->multilingualEvent($client);
        $target = static::getContainer()->get(EventChangeTarget::class);

        // Act
        $verdicts = [
            $target->validate((int) $event->getId(), 'start_en', 'nope'),
            $target->validate((int) $event->getId(), 'title_xx', 'nope'),
            $target->validate((int) $event->getId(), 'title_en', ''),
            $target->validate((int) $event->getId(), 'title_en', 'fine'),
        ];

        // Assert
        self::assertNotNull($verdicts[0], 'a property outside the translated text is refused');
        self::assertNotNull($verdicts[1], 'a locale the event does not carry is refused');
        self::assertNotNull($verdicts[2], 'a blank title is refused');
        self::assertNull($verdicts[3]);
    }

    /**
     * @return list<string>
     */
    private function localesOf(Event $event): array
    {
        $locales = [];
        foreach ($event->getTranslation() as $translation) {
            $locales[] = (string) $translation->getLanguage();
        }

        return $locales;
    }

    private function otherLocale(Event $event): string
    {
        foreach ($this->localesOf($event) as $locale) {
            if ($locale !== 'en') {
                return $locale;
            }
        }

        self::fail('Required multilingual event fixture missing');
    }

    private function multilingualEvent(KernelBrowser $client): Event
    {
        $events = $this->em($client)->createQuery(
            'SELECT e FROM ' . Event::class . ' e JOIN e.translations t GROUP BY e.id HAVING COUNT(t.id) > 1 ORDER BY e.id ASC',
        )->setMaxResults(1)->getResult();
        if ($events === []) {
            self::fail('Required multilingual event fixture missing');
        }

        return $events[0];
    }

    private function reloadEvent(KernelBrowser $client, int $id): Event
    {
        $em = $this->em($client);
        $em->clear();
        $event = $em->getRepository(Event::class)->find($id);
        if (!$event instanceof Event) {
            self::fail('Event vanished');
        }

        return $event;
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
