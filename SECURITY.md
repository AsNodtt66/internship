# Security Policy

## Supported baseline

Security fixes should target the current maintained application branch. Dependency major upgrades are handled in dedicated upgrade branches so security fixes are not obscured by unrelated framework changes.

## Reporting a vulnerability

Report suspected vulnerabilities **privately to the project maintainers/owner through the organization's approved private channel**. Do not publish credentials, personal documents, database dumps, exploit details, or participant data in a public issue/chat.

Include when possible:

- affected route/resource/action;
- role/account type used;
- minimal reproduction steps;
- expected vs actual authorization behavior;
- request ID/time window if relevant;
- screenshots with personal data redacted.

## Sensitive data

Never include the following in bug reports unless an authorized secure channel explicitly requires them:

- real KTP/KTM/BPJS/CV/transcript files;
- passwords or reset tokens;
- `APP_KEY`;
- database credentials;
- mail/API tokens;
- production database dumps.

## Security development rules

- Policy/Gate is the authorization boundary.
- Private files stay outside public webroot.
- Security controls must have regression tests when practical.
- Do not disable `strictAuthorization()` to bypass a broken permission.
- Do not weaken rate limits/security headers globally to fix one UX issue without root-cause analysis.
