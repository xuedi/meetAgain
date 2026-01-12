# Architecture Documentation

This document describes the architectural patterns and layer dependencies of the MeetAgain application.

---

## Overview

MeetAgain is a **Symfony 8.0 / PHP 8.4** event management system with a plugin architecture. It follows a layered architecture with strict dependency rules enforced by Deptrac.

**Technology Stack:**
- **Backend:** Symfony 8.0, PHP 8.4, Doctrine ORM 3.x
- **Frontend:** Bulma CSS, Font Awesome, Flatpickr, JSTable
- **Database:** MariaDB
- **Cache:** Valkey/Redis (Symfony Cache)
- **Testing:** PHPUnit 12, DAMA DoctrineTestBundle
- **Quality:** PHPStan level 9, Rector, PHP-CS-Fixer, Deptrac

---

## Layer Architecture

The application is organized into distinct layers with clear responsibilities and allowed dependencies.

```
┌─────────────────────────────────────────────────────────────┐
│                    Controllers & Commands                    │
│          (HTTP/CLI entry points, thin delegation)            │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ↓
┌─────────────────────────────────────────────────────────────┐
│                         Services                             │
│              (Business logic, readonly classes)              │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ↓
┌─────────────────────────────────────────────────────────────┐
│                       Repositories                           │
│            (Data access, query builder methods)              │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ↓
┌─────────────────────────────────────────────────────────────┐
│                         Entities                             │
│            (Pure data objects, Doctrine attributes)          │
└─────────────────────────────────────────────────────────────┘
```

---

## Layer Dependency Rules

Enforced by `tests/deptrac.yaml`:

### Controller Layer
**Can depend on:** Service, Entity, Form, Security, Repository
**Purpose:** Thin HTTP entry points that delegate to services

```php
// Example: src/Controller/ManageController.php
class ManageController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $repo,
    ) {}

    #[Route('/manage', name: self::ROUTE_MANAGE)]
    public function index(): Response
    {
        return $this->render('manage/index.html.twig', [
            'events' => $this->repo->findUpcomingEventsWithinRange(),
        ]);
    }
}
```

**Note:** Controllers should delegate to services, not repositories directly (TODO: refactor existing direct repository usage).

---

### Service Layer
**Can depend on:** Repository, Entity
**Purpose:** Business logic, orchestration, readonly classes with constructor injection

```php
// Example: src/Service/CleanupService.php
readonly class CleanupService
{
    public function __construct(
        private EventRepository $eventRepo,
        private ImageRepository $imageRepo,
        private EntityManagerInterface $em,
    ) {}

    public function removeOrphanedImages(): int
    {
        $orphaned = $this->imageRepo->findOrphaned();
        foreach ($orphaned as $image) {
            $this->em->remove($image);
        }
        $this->em->flush();

        return count($orphaned);
    }
}
```

**Key principles:**
- All services MUST be `readonly`
- Use constructor injection only
- Single Responsibility Principle
- No static methods

---

### Repository Layer
**Can depend on:** Entity
**Purpose:** Data access with intent-revealing method names

```php
// Example: src/Repository/EventRepository.php
class EventRepository extends ServiceEntityRepository
{
    public function findUpcomingEventsWithinRange(
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->where('e.start >= :now')
            ->andWhere('e.canceled = false')
            ->setParameter('now', $start ?? new DateTimeImmutable())
            ->orderBy('e.start', 'ASC');

        if ($end !== null) {
            $qb->andWhere('e.start <= :end')
               ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }
}
```

**Key principles:**
- Intent-revealing method names (not `getByStartDate()`, use `findUpcomingEventsWithinRange()`)
- Use QueryBuilder, not raw SQL
- Use array hydration for performance when entities not needed
- Avoid N+1 queries (use joins with `addSelect()`)

---

### Entity Layer
**Can depend on:** Nothing
**Purpose:** Pure data objects with Doctrine attributes

```php
// Example: src/Entity/Event.php
#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(enumType: EventTypes::class)]
    private EventTypes $type;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'rsvpEvents')]
    private Collection $rsvps;

    // Getters and setters...
}
```

**Key principles:**
- Use Doctrine attributes (not annotations)
- Enums for status/type fields
- Collections must have docblock: `@var Collection<int, User>`
- No business logic in entities

---

### Supporting Layers

#### Form Layer
**Can depend on:** Entity, Service, Repository
**Purpose:** Form type classes for validation and rendering

#### Command Layer
**Can depend on:** Service, Entity
**Purpose:** CLI commands (similar to controllers)

#### Security Layer
**Can depend on:** Entity, Repository, Service
**Purpose:** Authentication, authorization (UserChecker, Voters)

#### Twig Layer
**Can depend on:** Entity, Service
**Purpose:** Twig extensions for presentation helpers

#### EventSubscriber Layer
**Can depend on:** Service, Entity
**Purpose:** React to Symfony events

#### DataFixtures Layer
**Can depend on:** Entity, Repository, Service
**Purpose:** Test data (allowed more flexibility)

---

## Plugin Architecture

The application supports a plugin system for extensibility.

**Key principles:**
- Plugins implement `Plugin` interface
- Main code MUST NOT depend on plugin code
- Plugin tables have no foreign keys to main tables
- Main application must work when plugins are disabled
- Integration points check if plugin is enabled before calling it

**How plugins are called:**
```php
// EventService.php - Integration point example
public function getPluginEventTiles(int $id): array
{
    $enabledPlugins = $this->pluginService->getActiveList();
    $tiles = [];
    foreach ($this->plugins as $plugin) {
        // ✅ Check if plugin is enabled before calling
        if (!in_array($plugin->getPluginKey(), $enabledPlugins, true)) {
            continue;  // Skip disabled plugins
        }
        $tile = $plugin->getEventTile($id);
        if ($tile !== null) {
            $tiles[] = $tile;
        }
    }
    return $tiles;
}
```

**Plugin Interface:**
```php
interface Plugin
{
    public function getPluginKey(): string;
    public function getMenuLinks(): array;
    public function getEventTile(Event $event): ?PluginTile;
    public function loadPostExtendFixtures(ObjectManager $manager): void;
}
```

**Example:** `src/Plugin/Dishes/DishesPlugin.php`

---

## Symfony 8 Specific Features

### New in Symfony 8.0 (used in this project)

1. **AssetMapper** - Modern asset management (no Webpack/Encore needed)
2. **HTTP/2 & HTTP/3 Early Hints** - Performance optimization for assets
3. **Scheduler Component** - Cron-like task scheduling
4. **Clock Component** - Time testing utilities
5. **TypedEnum Constraint** - Validates backed enums
6. **RateLimiter Improvements** - Better rate limiting strategies

### Symfony Features in Active Use

**Example: Early Hints**
```php
// Controllers use early hints for asset preloading
#[Route('/event/{id}', name: 'app_event_details')]
public function details(int $id): Response
{
    $response = $this->getResponse(); // Early hints support
    return $this->render('events/details.html.twig', [...], $response);
}
```

**Example: AssetMapper**
```php
// Templates use asset() function with AssetMapper
<link href="{{ asset('styles/app.css') }}" rel="stylesheet">
```

---

## Database Schema Patterns

### Naming Conventions

- **Tables:** snake_case, singular (e.g., `event`, `user`)
- **Columns:** snake_case (e.g., `created_at`, `event_type`)
- **Foreign keys:** `{table}_id` (e.g., `user_id`)
- **Junction tables:** `{table1}_{table2}` (e.g., `event_user` for RSVP)

### Enums

Stored as `VARCHAR` with Doctrine's `enumType`:

```php
#[ORM\Column(enumType: EventTypes::class)]
private EventTypes $type;

enum EventTypes: string
{
    case All = 'all';
    case Meeting = 'meeting';
    case Social = 'social';
}
```

### Translations

Translation system uses `Translation` entity:
- `language` (ISO 639-1 code)
- `placeholder` (translation key)
- `translation` (translated text)
- Unique constraint on `(language, placeholder)`

---

## Caching Strategy

**Symfony Cache (Valkey/Redis):**
- Used for translations, menu items, plugin configuration
- Tagged cache invalidation (e.g., `translations` tag)
- Early hints caching for HTTP/2 performance

**Example:**
```php
$cache->get('menu_items', function() {
    return $this->buildMenuItems();
});

// Invalidation
$cache->invalidateTags(['menu']);
```

---

## Known Architectural Debt

From `tests/deptrac.yaml` skip violations:

1. **MenuRoutes enum references controllers** for route names
   - Location: `src/Entity/MenuRoutes.php`
   - TODO: Refactor to use route string constants

2. **EventSubscriber references controller** for route comparison
   - Location: `src/EventSubscriber/KernelRequestSubscriber.php`
   - TODO: Use route names instead

3. **Twig extension references controller**
   - Location: `src/Twig/RenderImageModalExtension.php`
   - TODO: Refactor reference pattern

4. **Commands with direct repository access**
   - Location: `src/Command/EventAddFixtureCommand.php`, `src/Command/EmailTemplateSeedCommand.php`
   - Reason: Bulk operations, performance

---

## Anti-Patterns to Avoid

1. **Fat Controllers** - Business logic in controllers
2. **Repository in Controller** - Should use services (architectural debt exists)
3. **Static Methods** - Use dependency injection
4. **Direct DB queries** - Use Doctrine QueryBuilder
5. **Tight coupling** - Respect layer boundaries
6. **Missing readonly** - All services must be readonly
7. **Mutable services** - No state in services
8. **God objects** - Classes with too many responsibilities

---

## Validation with Deptrac

Run architecture validation:
```bash
just checkDeptrac
```

This enforces all layer dependency rules and fails if violations are introduced.

---

**Related Documentation:**
- [Conventions](conventions.md) - Coding standards
- [Testing](testing.md) - Testing strategies
