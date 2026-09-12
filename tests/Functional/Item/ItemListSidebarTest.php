<?php declare(strict_types=1);

namespace Tests\Functional\Item;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ItemListSidebarTest extends WebTestCase
{
    private const string GLOSSARY_HOST = 'dragon.meetagain.local';
    private const string FILM_HOST = 'cinema.meetagain.local';
    private const string PHOTO_HOST = 'photo.meetagain.local';

    /** @return iterable<string, array{string, string, string}> */
    public static function listPageProvider(): iterable
    {
        yield 'glossary' => [self::GLOSSARY_HOST, '/en/glossary', 'glossary'];
        yield 'films' => [self::FILM_HOST, '/en/films', 'film'];
    }

    #[DataProvider('listPageProvider')]
    public function testSidebarRendersBesideTheList(string $host, string $url, string $itemType): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', $url, server: ['HTTP_HOST' => $host]);

        // Assert
        $this->assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.item-list-layout > .item-list-sidebar'));
        static::assertCount(1, $crawler->filter('.item-list-layout > .item-list-main'));
        static::assertCount(
            1,
            $crawler->filter('.item-list-sidebar a[href$="/item/' . $itemType . '/view/list"]'),
            'The view switcher belongs to the sidebar',
        );
        static::assertStringContainsString(
            'item-list-sidebar',
            (string) $crawler->filter('.item-list-layout > .column')->first()->attr('class'),
            'The sidebar comes first in the DOM so it stacks above the list on narrow viewports',
        );
    }

    #[DataProvider('listPageProvider')]
    public function testResultHeaderStatesTheListSizeInsideTheSwappedRegion(string $host, string $url, string $itemType): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', $url, server: ['HTTP_HOST' => $host]);

        // Assert
        $rows = $crawler->filter('[data-item-list-scope="' . $itemType . '"] .item-list tbody tr')->count();
        static::assertGreaterThan(0, $rows);
        static::assertStringContainsString(
            (string) $rows,
            $crawler->filter('[data-item-list-body] .item-result-header')->text(),
        );
    }

    public function testViewSwitcherIsTheFirstSidebarBox(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $boxes = $crawler->filter('.item-list-sidebar .box');
        static::assertCount(
            1,
            $boxes->first()->filter('a[href$="/item/glossary/view/list"]'),
            'Box order is view -> filter -> about',
        );
        static::assertStringContainsString('item-tag-filter', (string) $boxes->eq(1)->attr('class'));
    }

    public function testEveryFacetOptionIsAChipAndAnEmptyOneIsNotClickable(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $links = $crawler->filter('[data-item-filter] a.tag[data-item-facet]');
        static::assertGreaterThan(0, $links->count());
        static::assertSame('nofollow', $links->first()->attr('rel'));
        static::assertGreaterThan(
            0,
            $crawler->filter('[data-item-filter] span.tag')->count(),
            'An option that would yield nothing renders as a dimmed span, never a link',
        );
    }

    public function testEveryTagSitsOnItsOwnLineAndASubTagHangsFromAnArrow(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $rows = $crawler->filter('[data-item-facet-axis="tag"] > div');
        static::assertGreaterThan(1, $rows->count());
        static::assertSame([1], array_values(array_unique($rows->each(static fn(Crawler $row): int => $row->filter('.tag')->count()))));
        static::assertGreaterThan(0, $crawler->filter('[data-item-facet-axis="tag"] [data-item-tag-indent]')->count());
    }

    public function testOptionsPastTheTwelfthCollapseBehindShowAll(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/photos', server: ['HTTP_HOST' => self::PHOTO_HOST]);
        $rows = $crawler->filter('[data-item-facet-axis="tag"] > div');
        if ($rows->count() <= 12) {
            static::markTestSkipped('The photo fixtures here seed no vocabulary longer than the twelve visible chips.');
        }

        // Assert
        static::assertCount($rows->count() - 12,$crawler->filter('[data-item-facet-axis="tag"] > div.item-facet-extra'));
        static::assertCount(0, $crawler->filter('.item-facet-extra.is-flex'), 'is-flex is !important and would override the is-hidden that collapses the row');
        static::assertCount(1, $crawler->filter('[data-item-facet-axis="tag"] > [data-item-facet-more]'));
    }

    public function testASubTagNeverOutcountsTheParentItHangsFrom(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $rows = $crawler->filter('[data-item-facet-axis="tag"] > div')->each(static fn(Crawler $row): array => [
            'depth' => (int) ($row->filter('[data-item-tag-indent]')->count() > 0 ? $row->filter('[data-item-tag-indent]')->attr('data-item-tag-indent') : 1),
            'count' => (int) $row->filter('.tag > span')->text(),
        ]);
        $checked = 0;
        foreach ($rows as $index => $row) {
            if ($row['depth'] < 2) {
                continue;
            }

            $parent = array_find(array_reverse(array_slice($rows, 0, $index)), static fn(array $candidate): bool => $candidate['depth'] === $row['depth'] - 1);
            static::assertNotNull($parent);
            static::assertGreaterThanOrEqual($row['count'], $parent['count']);
            $checked++;
        }
        static::assertGreaterThan(0, $checked);
    }

    public function testAFacetedPageNarrowsTheCountAndIsNotIndexed(): void
    {
        // Arrange
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);
        $total = $crawler->filter('.item-list tbody tr')->count();
        $chip = $crawler->filter('[data-item-filter] a.tag[data-item-facet]')->first();

        // Act
        $faceted = $client->request('GET', (string) $chip->attr('href'), server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        $this->assertResponseIsSuccessful();
        $narrowed = $faceted->filter('.item-list tbody tr')->count();
        static::assertLessThan($total, $narrowed);
        static::assertStringContainsString(
            (string) $total,
            $faceted->filter('.item-result-header')->text(),
            'The header states the narrowed count against the unfaceted total',
        );
        static::assertCount(
            1,
            $faceted->filter('meta[name="robots"][content="noindex,follow"]'),
        );
        static::assertGreaterThan(
            0,
            $faceted->filter('.item-result-header a[data-item-facet]')->count(),
            'The active facet is repeated as a removable chip',
        );
    }

    public function testAnUnfacetedPageStaysIndexable(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        static::assertCount(0, $crawler->filter('meta[name="robots"]'));
    }

    public function testGlossarySidebarOffersOnlyItsTwoModes(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/en/glossary', server: ['HTTP_HOST' => self::GLOSSARY_HOST]);

        // Assert
        static::assertCount(2, $crawler->filter('.item-list-sidebar a[href*="/item/glossary/view/"]'));
    }
}
