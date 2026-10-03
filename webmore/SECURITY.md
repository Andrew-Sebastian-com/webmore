# Security

Webmore includes defensive measures for a small PHP starter application, including protected sessions, CSRF tokens, output escaping, private runtime storage, upload validation, and server-side authorization checks.

## Before publishing or deploying

- Do not commit `private/data/*.json` or anything under `private/uploads/`.
- Do not commit passwords, API keys, tokens, SMTP credentials, or other secrets.
- Replace placeholder contact information in `public/config.php`.
- Run the application behind HTTPS in production.
- Use a production database and a proper backup/recovery plan rather than treating local JSON storage as a production database.
- Review the privacy policy, terms, age-assurance approach, moderation process, and applicable laws for the jurisdiction where Webmore operates.

## Reporting vulnerabilities

Do not disclose a suspected vulnerability through a public issue. Use the private security contact configured by the project maintainer.
