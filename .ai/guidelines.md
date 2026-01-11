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

## Architecture & Software Choices

### Date/Time Picker
- **Library**: Flatpickr (https://flatpickr.js.org)
- **Styling**: Bulma theme (`/stylesheet/flatpickr-bulma.min.css`)
- **Localization**: Language files in `/javascript/flatpickr-l10n/{locale}.js`
- **Configuration**: Uses `get_date_format_flatpickr()` Twig function for format
- **Usage**:
  - Use `DateTimeType::class` with `'widget' => 'single_text'` in Symfony forms
  - The form theme (`_form/bulma.html.twig`) wraps datetime fields with `.flatpickr-field` class
  - Include CSS/JS and initialization script in templates (see `templates/admin/base.html.twig`)

### Frontend Framework
- **CSS Framework**: Bulma (no custom CSS frameworks)
- **Icons**: Font Awesome
- **Tables**: JSTable for sortable/searchable tables in admin

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

