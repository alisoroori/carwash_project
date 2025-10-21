# Contributing

Thank you for contributing to CarWash.

Quick rules:
- Fork the repo and create a feature branch: `git checkout -b feature/my-change`
- Run tests locally: `composer install && vendor/bin/phpunit`
- Follow PSR-12; run linters before PR.
- Do not commit `vendor/`, `node_modules/`, or secrets (`.env`, keys).
- If you find a secret in history: notify maintainers, rotate the secret immediately, and coordinate history cleanup.
- For large/histories changes (BFG/git-filter-repo): open an issue and coordinate with maintainers.

PR checklist:
- [ ] Tests added / updated
- [ ] README/docs updated
- [ ] No secrets in commits
- [ ] Linted / Coding style OK