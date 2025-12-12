# AI Contribution Guidelines

## Development Environment
- All services run in Docker containers. Never run commands directly on the host.
- Always use `just` (justfile) commands to interact with the project (e.g., `just test`, `just app`).

## Coding Standards
- PHP: PSR-12, strict types where feasible.
- Twig: Keep templates simple; push logic to controllers/services when appropriate.

## Symfony Conventions
- Controllers: Thin; delegate to services.
- Services: Register via Symfony autowiring and attributes as per existing patterns.
- Configuration: Use config/packages and environment variables (.env, .env.local), never hard-code secrets.

## Security and Privacy
- Input validation: Validate and sanitize input; use Symfony Validator and CSRF protection.
- Authentication/Authorization: Use security voters/attributes consistently. Least privilege.

## Error Handling
- Exceptions: Throw domain-specific exceptions where helpful.

## Performance
- Consider complexity of queries and N+1 issues (use joins, eager loading when needed).
- Cache where appropriate (Symfony cache pools) but avoid premature optimization.

## Emails and Notifications
- Use the existing EmailService and NotificationService patterns; avoid duplicating logic.
- Separate rendering (Twig templates) from sending; inject dependencies.

## Frontend Templates
- Dont use inline scripts.
- Use minimalist Bulma elements.

## Testing
- Prefer unit tests for services and functional tests for controllers/routes.
- Ignore the command `just update_coverage_badge` this is only for the CI to create badges for humans