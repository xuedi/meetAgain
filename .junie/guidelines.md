# AI Contribution Guidelines
Last updated: 2025-10-19 01:16 (local)

## Goals and Scope
- Maintain code quality, security, and consistency across the project.
- Preserve project conventions: Symfony (PHP), Twig, Doctrine, PSR standards, and existing tooling.

## Development Environment
- All services run in Docker containers. Never run commands directly on the host.
- Always use `just` (justfile) commands to interact with the project (e.g., `just test`, `just app`).
- Run `just` without arguments to see all available commands.
- Read the comments in the `justfile` to understand what each command does.
- Always use `just test` to run tests, not `just do "vendor/bin/phpunit ..."`.
- Always use `just clearCache` to clear cache (clears all environments).

## General Principles
- Prefer clarity over cleverness. Choose explicit, readable solutions.
- Follow existing patterns in the codebase. When in doubt, search for similar implementations.
- Document decisions in code comments or in the PR description when behavior changes.

## Coding Standards
- PHP: PSR-12, strict types where feasible.
- Naming: Follow Symfony/Doctrine conventions (entities, services, controllers).
- Twig: Keep templates simple; push logic to controllers/services when appropriate.

## Symfony Conventions
- Controllers: Thin; delegate to services.
- Services: Register via Symfony autowiring and attributes as per existing patterns.
- Configuration: Use config/packages and environment variables (.env, .env.local), never hard-code secrets.

## Security and Privacy
- Secrets: Never commit secrets. Use env vars and Symfony vault if configured.
- Input validation: Validate and sanitize input; use Symfony Validator and CSRF protection.
- Authentication/Authorization: Use security voters/attributes consistently. Least privilege.

## Error Handling and Observability
- Exceptions: Throw domain-specific exceptions where helpful;
- Logging: Use PSR-3 logger; ensure log levels are appropriate.

## Dependencies
- Be conservative adding new packages; justify necessity and security posture.
- Pin versions according to composer constraints; run "composer update --lock" only when required.
- Remove unused dependencies if encountered.

## Performance
- Consider complexity of queries and N+1 issues (use joins, eager loading when needed).
- Cache where appropriate (Symfony cache pools) but avoid premature optimization.

## Emails and Notifications
- Use the existing EmailService and NotificationService patterns; avoid duplicating logic.
- Separate rendering (Twig templates) from sending; inject dependencies.

## Frontend Templates
- Dont use inline scripts.
- User minimalist Bulma elements.

## Testing
- Add or update tests for changed behavior.
- Prefer unit tests for services and functional tests for controllers/routes.
- Always run the test with `just test`
- Ignore the command `just update_coverage_badge` this is only for the CI to create badges for humans
