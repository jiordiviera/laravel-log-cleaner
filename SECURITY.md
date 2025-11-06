# Security Policy

## Supported Versions

We actively support the following versions with security updates:

| Version | Supported          | Support Until  | Notes                    |
| ------- | ------------------ | -------------- | ------------------------ |
| 2.0.x   | :white_check_mark: | Active         | Current stable release   |
| 1.0.x   | :warning:          | 2026-01-01     | Security fixes only      |
| < 1.0   | :x:                | End of life    | No longer supported      |

## Reporting a Vulnerability

We take the security of Laravel Log Cleaner seriously. If you discover any security-related issues, please send an email to <hello@jiordiviera.me> instead of using the public issue tracker.

Please include the following information in your report:

- Type of issue (e.g., buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### What to expect

- You will receive acknowledgement of your report within 48 hours
- We will try to keep you informed about our progress throughout the process
- After the initial reply to your report, the security team will endeavor to keep you informed of the progress towards a fix and full announcement
- You may be asked for additional information or guidance

### Security Update Process

- After receiving a security report, we will verify the issue and determine its severity
- If the issue is confirmed:
  - We will develop and test a fix
  - We will prepare a security advisory for our users
  - The fix will be applied to the latest stable version and released
  - After the release, the security advisory will be published

### Public Disclosure Process

- Security vulnerabilities must be disclosed privately and will be handled on a case-by-case basis
- Public disclosure of a vulnerability will happen after a fix has been released
- The timing of the public disclosure will be coordinated between the security team and the reporter

## Security Best Practices

When using Laravel Log Cleaner, please follow these security best practices:

1. Always keep your Laravel Log Cleaner package up to date with the latest version
2. Use appropriate file permissions on your log files
3. Configure log retention periods according to your compliance requirements
4. Regularly monitor log cleaning activities
5. Implement proper access controls to log directories
6. Use environment variables for sensitive configurations

## Dependencies

Laravel Log Cleaner is built on top of Laravel and follows Laravel's security policies and best practices. We regularly monitor our dependencies for security issues and update them as needed.

## Contact

For any questions regarding this security policy, please contact jiordikengne@gmail.com.

Thank you for helping keep Laravel Log Cleaner and its users safe!
