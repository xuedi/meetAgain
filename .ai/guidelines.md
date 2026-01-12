# AI Contribution Guidelines

## Environment

- Docker only via `just` command runner - never run commands on host
- Run `just` to see available commands (don't parse the justfile itself)
- Avoid `just test` to save tokens, ask the user instead to run it after a coding task is complete.
- If you have to run a test, use `just testUnit <specific test>` only on the test to be run.

## PHP Style

- PSR-12, `declare(strict_types=1)`, use `readonly` where applicable
- Use `use` statements (no FQCNs in code)
- Omit `@param`/`@return` when native types exist
- Constructor property promotion with dependency injection

## Architecture

- **Controllers**: Thin, extend `AbstractController`, delegate to services
- **Services**: Business logic, `readonly` class with constructor injection
- **Repositories**: Data access only, custom query methods with intent-revealing names
- **Entities**: Doctrine attributes, enums for status/type fields

## Frontend

- **CSS**: Bulma only (no other frameworks)
- **Icons**: Font Awesome
- **Date picker**: Flatpickr - see `templates/admin/base.html.twig`
- **Admin tables**: JSTable for sortable/searchable
- No inline scripts

## Plugins

- Implement `Plugin` interface (`getPluginKey`, `getMenuLinks`, `getEventTile`, `loadPostExtendFixtures`)
- Main code must not depend on plugin code
- Plugin tables must not have foreign keys to main tables
- Plugins must work when disabled - integration points filter by enabled status

## Testing

- Unit tests for services, functional tests for controllers
- Use `// Arrange`, `// Act`, `// Assert` comments
- `createStub()` for doubles, `createMock()` only when verifying interactions
- Fixtures provide test data (see `src/DataFixtures/`)

## Security

- Symfony Validator + CSRF for input
- Security voters/attributes for authorization

## Commands

| Command                | Purpose                       |
|------------------------|-------------------------------|
| `just test`            | Run all tests and checks      |
| `just devModeFixtures` | Reset dev with fixtures       |
| `just routeMetrics`    | Analyze routes (SQL, timing)  |
| `just showCoverage`    | Show files below 80% coverage |
| `just dockerDatabase`  | run a query on the database   |
