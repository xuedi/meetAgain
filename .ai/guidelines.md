# AI Contribution Guidelines

## Environment
- Docker only via `just` (command runner) - never run on host
- Run `just` to see all available commands with descriptions (don't read the justfile)

## Code Style
- PHP: PSR-12, strict types, use `use` statements (no FQCNs), omit `@param`/`@return` when native types exist
- Twig: Simple templates, logic in controllers/services
- Frontend: No inline scripts, Bulma only

## Symfony
- Controllers: Thin, delegate to services
- Services: Autowiring + attributes
- Config: `.env` + `config/packages`, no hardcoded secrets

## Security
- Validate input: Symfony Validator + CSRF
- Use security voters/attributes, least privilege

## Plugins
- Main code must not depend on plugin code
- Plugin tables must not have foreign keys to main tables
- Plugins must be deactivatable without breaking the app
- Integration into the main app via plugin interface

## Performance
- Avoid N+1 queries (use joins/eager loading)
- Use Symfony cache pools

## Email/Notifications
- Follow existing EmailService/NotificationService patterns
- Separate rendering (Twig) from sending

## Testing (Required for new code)
- Unit tests for services, functional for controllers
- Use Arrange/Act/Assert comments
- `createStub()` for test doubles, `createMock()` only when verifying interactions

## Analysis Commands
| Command                              | Purpose                              |
|--------------------------------------|--------------------------------------|
| `just test`                          | Run all tests                        |
| `just devModeFixtures`               | Reset dev environment with fixtures  |
| `just routeMetrics`                  | Analyze routes (SQL, timing, memory) |
| `just routeMetrics --filter=pattern` | Filter by route name                 |
| `just showCoverage`                  | Show files <80% coverage             |
| `just showCoverage --threshold=N`    | Custom threshold                     |
| `just fixCoverageBadge`              | Update badge (auto-stages)           |
| `just app app:translation:missing`   | Find all missing translations        |

