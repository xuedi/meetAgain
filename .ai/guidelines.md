# AI Contribution Guidelines

## Environment
- Use Docker only - never run commands on host
- Use `just` commands: `just test`, `just app`, `just dockerDatabase {statement}`

## Code Style
- PHP: PSR-12, strict types, omit `@param`/`@return` when native type hints exist
- Twig: Simple templates, logic in controllers/services
- Frontend: No inline scripts, Bulma components only
- Always use the php `use` statement & avoid fully qualified class names for readibility in the code

## Symfony
- Controllers: Thin, delegate to services
- Services: Autowiring + attributes
- Config: `.env` + `config/packages`, never hard-code secrets

## Security
- Validate/sanitize input, use Symfony Validator + CSRF
- Security voters/attributes, least privilege

## Performance
- Avoid N+1 queries (joins/eager loading)
- Use Symfony cache pools, no premature optimization

## Email/Notifications
- Use existing EmailService/NotificationService patterns
- Separate rendering (Twig) from sending

## Testing
- **Always add tests when developing new code**
- Unit tests for services, functional for controllers
- Comment test sections: Arrange/Act/Assert
- `createStub()` for no expectations, `createMock()` for verification only

### Performance Analysis
- `just routeMetrics` - analyzes all routes for SQL queries, timing, and memory
- `just routeMetrics --filter=app_admin` - filter metrics for specific route names or patterns

### Coverage Analysis
- `just showCoverage` - runs tests + shows compact report (files <80% coverage)
- `just showCoverage --threshold=50` - show only files below 50%
- `just fixCoverageBadge` - update badge (runs all tests, auto-stages)
- Target HIGH/MED impact files first (shown in report)
