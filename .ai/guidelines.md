# AI Contribution Guidelines

## Environment
- Use Docker only - never run commands on host
- Use `just` commands: `just test`, `just app`, `just dockerDatabase {statement}`

## Code Style
- PHP: PSR-12, strict types, omit `@param`/`@return` when native type hints exist
- Twig: Simple templates, logic in controllers/services
- Frontend: No inline scripts, Bulma components only

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
- Unit tests for services, functional for controllers
- Comment test sections: Arrange/Act/Assert
- `createStub()` for no expectations, `createMock()` for verification only

### Coverage Analysis
- **Check coverage**: `just showCoverage` - runs all tests + shows AI-readable report
- **Find opportunities**: `just showCoverage --threshold=50` - show files below 50%
- **Sort by impact**: `just showCoverage --sort=uncovered` - biggest impact first
- **Update badge**: `just fixCoverageBadge` - runs all tests + updates badge (auto-stages)
- Focus on low-hanging fruit: files with <60% coverage and many uncovered elements
- Tool outputs color-coded sections (🔴 Low, 🟡 Medium, 🟢 High) with impact ratings
- Note: `just showCoverage` automatically runs tests first, no need to run separately

### Test Creation Workflow
1. Run `just showCoverage --threshold=60` to find opportunities
2. Look for 🔥 HIGH IMPACT files in the output
3. Create tests using existing test patterns (check similar service tests)
4. Verify with `just testUnit` or `just test`
5. Update coverage: `just fixCoverageBadge`