<?php declare(strict_types=1);

namespace Module\Trust\Tests\Functional;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Module\Trust\Contract\TrustInterface;
use Module\Trust\Contract\TrustLevel;
use Module\Trust\Internal\ConfigStore;
use Module\Trust\Internal\Entity\TrustContextConfig;
use Module\Trust\Internal\ScoreProvider;
use Module\Trust\Tests\Stub\ActionSource;
use Module\Trust\Tests\Stub\ContextDescriber;
use Module\Trust\Tests\Stub\UserLocator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class StubConsumerTest extends KernelTestCase
{
    private const string CONTEXT = ContextDescriber::CONTEXT;

    public function testAVouchLiftsAMemberOverTheParticipationMinimum(): void
    {
        // Arrange
        static::bootKernel();
        $locator = static::getContainer()->get(UserLocator::class);
        $trust = static::getContainer()->get(TrustInterface::class);
        $rootId = (int) $locator->idFor(UserLocator::ROOT_EMAIL);
        $newcomerId = (int) $locator->idFor(UserLocator::NEWCOMER_EMAIL);
        $this->configure(['minimumToParticipate' => 200]);

        // Act
        $before = $trust->meetsMinimum(self::CONTEXT, $newcomerId);
        $trust->grant(self::CONTEXT, $rootId, $newcomerId, TrustLevel::Absolute);
        $after = $trust->meetsMinimum(self::CONTEXT, $newcomerId);

        // Assert
        static::assertFalse($before);
        static::assertTrue($after);
        static::assertSame(500, $trust->getScore(self::CONTEXT, $newcomerId));
    }

    public function testAQuantityCapKeepsTenureFromRunningAway(): void
    {
        // Arrange
        static::bootKernel();
        $locator = static::getContainer()->get(UserLocator::class);
        $trust = static::getContainer()->get(TrustInterface::class);
        $earnerId = (int) $locator->idFor(UserLocator::EARNER_EMAIL);
        $capped = $trust->getScore(self::CONTEXT, $earnerId);

        // Act
        $this->configure(['capsPerAction' => [ActionSource::TENURE => ActionSource::TENURE_MONTHS]]);
        $uncapped = $trust->getScore(self::CONTEXT, $earnerId);

        // Assert
        static::assertSame(ActionSource::HANDOVERS * ActionSource::DEFAULT_POINTS + ActionSource::TENURE_CAP, $capped);
        static::assertSame(ActionSource::HANDOVERS * ActionSource::DEFAULT_POINTS + ActionSource::TENURE_MONTHS, $uncapped);
    }

    public function testActionPointsAloneProduceAScore(): void
    {
        // Arrange
        static::bootKernel();
        $locator = static::getContainer()->get(UserLocator::class);
        $trust = static::getContainer()->get(TrustInterface::class);
        $earnerId = (int) $locator->idFor(UserLocator::EARNER_EMAIL);

        // Act
        $score = $trust->getScore(self::CONTEXT, $earnerId);

        // Assert
        $expected = ActionSource::HANDOVERS * ActionSource::DEFAULT_POINTS + ActionSource::TENURE_CAP;
        static::assertSame($expected, $score);
    }

    public function testAnUndeclaredActionIsIgnoredAndReported(): void
    {
        // Arrange
        static::bootKernel();
        $provider = static::getContainer()->get(ScoreProvider::class);

        // Act
        $undeclared = $provider->findUndeclaredActions(self::CONTEXT);

        // Assert
        static::assertSame(['stub_never_declared'], $undeclared);
    }

    public function testRaisingThePointsPerHandoverMovesEverybodyWhoEverEarned(): void
    {
        // Arrange
        static::bootKernel();
        $locator = static::getContainer()->get(UserLocator::class);
        $trust = static::getContainer()->get(TrustInterface::class);
        $earnerId = (int) $locator->idFor(UserLocator::EARNER_EMAIL);
        $before = $trust->getScore(self::CONTEXT, $earnerId);

        // Act
        $this->configure(['pointsPerAction' => [ActionSource::HANDOVER => 50]]);
        $after = $trust->getScore(self::CONTEXT, $earnerId);

        // Assert
        $tenure = ActionSource::TENURE_CAP;
        static::assertSame(ActionSource::HANDOVERS * ActionSource::DEFAULT_POINTS + $tenure, $before);
        static::assertSame(ActionSource::HANDOVERS * 50 + $tenure, $after);
    }

    public function testTwoContextsScoreTheSameMembersIndependently(): void
    {
        // Arrange
        static::bootKernel();
        $locator = static::getContainer()->get(UserLocator::class);
        $trust = static::getContainer()->get(TrustInterface::class);
        $earnerId = (int) $locator->idFor(UserLocator::EARNER_EMAIL);

        // Act
        $described = $trust->getScore(self::CONTEXT, $earnerId);
        $undescribed = $trust->getScore('nobody-describes-this', $earnerId);

        // Assert
        static::assertGreaterThan(0, $described);
        static::assertSame(0, $undescribed);
    }

    public function testAMemberNeverSeesAnotherMembersOutgoingVouches(): void
    {
        // Arrange
        static::bootKernel();
        $locator = static::getContainer()->get(UserLocator::class);
        $trust = static::getContainer()->get(TrustInterface::class);
        $rootId = (int) $locator->idFor(UserLocator::ROOT_EMAIL);
        $earnerId = (int) $locator->idFor(UserLocator::EARNER_EMAIL);
        $newcomerId = (int) $locator->idFor(UserLocator::NEWCOMER_EMAIL);
        $trust->grant(self::CONTEXT, $rootId, $newcomerId, TrustLevel::Trusted);

        // Act
        $ownEdges = $trust->getOutgoing(self::CONTEXT, $earnerId);

        // Assert
        static::assertSame([], $ownEdges);
        static::assertSame(1, $trust->getVouchCount(self::CONTEXT, $newcomerId));
    }

    public function testTheTableOffersAVouchControlForEveryOtherMember(): void
    {
        // Arrange
        static::bootKernel();
        $container = static::getContainer();
        $root = $container->get(UserRepository::class)->findOneBy(['email' => UserLocator::ROOT_EMAIL]);
        \assert($root !== null);
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get(RequestStack::class)->push($request);
        $container->get(TokenStorageInterface::class)->setToken(new UsernamePasswordToken($root, 'main', $root->getRoles()));
        $earnerId = (int) $container->get(UserLocator::class)->idFor(UserLocator::EARNER_EMAIL);

        // Act
        $html = $container->get('twig')->createTemplate('{{ trust_table(context) }}')->render(['context' => self::CONTEXT]);

        // Assert
        $vouchTargets = new Crawler($html)->filter('form.trust-vouch input[name="user"]')->extract(['value']);
        static::assertContains((string) $earnerId, $vouchTargets);
        static::assertNotContains((string) $root->getId(), $vouchTargets);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function configure(array $payload): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->persist(new TrustContextConfig(self::CONTEXT, $payload, new DateTimeImmutable()));
        $entityManager->flush();
        $container->get(ConfigStore::class)->reset();
        $container->get(ScoreProvider::class)->invalidate(self::CONTEXT);
    }
}
