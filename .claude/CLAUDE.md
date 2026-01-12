# Claude Code Guidelines

## Environment

- Docker only via `just` command runner - never run commands on host
- Run `just` to see available commands (don't parse the justfile itself)
- Avoid `just test` to save tokens, ask the user instead to run it after a coding task is complete
- If you have to run a test, use `just testUnit <specific test>` only on the test to be run

## PHP Style

- PSR-12, `declare(strict_types=1)`, use `readonly` where applicable
- Minimize comments; omit if code is self-explanatory
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

## Token Efficiency

- Default to `model: "sonnet"` for subagents, use `model: "opus"` only for planning/architecture
- **Always use `model: "haiku"` for running `just` commands** (see Haiku Agent section below)
- Prefer asking user to run tests locally over AI execution
- For codebase exploration, use `subagent_type: "Explore"` over multiple greps
- Only read files that are directly relevant to the task
- Use offset/limit when reading large files

## Haiku Agent for `just` Commands

All `just` command execution MUST use the Haiku model via Task tool:

```
Task(
  subagent_type: "Bash",
  model: "haiku",
  prompt: "Run: just testUnit Tests\\Unit\\Service\\ExampleTest && just testResults"
)
```

**Pattern for running tests:**
1. Run test command: `just testUnit [TestClass]` (generates JUnit XML)
2. Get results: `just testResults` (AI-readable format)
3. Return structured summary to parent agent

**AI-readable output format:**
```
STATUS: PASSED|FAILED
SUMMARY: X tests, Y assertions, Z failures
---
FAILURES:
  1. ClassName::methodName
     File: path/to/file.php:123
     Message: Failed asserting...
     Expected: 'foo' | Actual: 'bar'
```

**Available test result commands:**
- `just testResults` - Show all test results
- `just testResults --failures-only` - Show only failures

## Commands

| Command                | Purpose                           |
|------------------------|-----------------------------------|
| `just test`            | Run all tests and checks          |
| `just testUnit`        | Run unit tests (generates JUnit)  |
| `just testFunctional`  | Run functional tests              |
| `just testResults`     | AI-readable test results          |
| `just devModeFixtures` | Reset dev with fixtures           |
| `just routeMetrics`    | Analyze routes (SQL, timing)      |
| `just showCoverage`    | Show files below 80% coverage     |
| `just dockerDatabase`  | Run a query on the database       |

## Quick References

| Pattern         | Example File                                 | Why                                    |
|-----------------|----------------------------------------------|----------------------------------------|
| Service         | `src/Service/CleanupService.php`             | Focused SRP, minimal dependencies      |
| Controller      | `src/Controller/ManageController.php`        | Thin, pure delegation (30 lines)       |
| Repository      | `src/Repository/EventRepository.php`         | Intent-revealing names, query builder  |
| Entity + Enums  | `src/Entity/Event.php`                       | Proper attributes, enum usage          |
| Plugin          | `src/Plugin.php`                             | Interface contract definition          |
| Unit test (AAA) | `tests/Unit/Service/ActivityServiceTest.php` | Excellent AAA comments, mock/stub use  |
