# Admin Activity Summary Command

## Overview
The `admin:activity-summary` command generates and emails a comprehensive summary of site activity to the administrator. This command is designed to help administrators monitor site usage and content creation trends.

## Usage

### Manual Execution
```bash
# Run with default 7-day summary
php artisan admin:activity-summary

# Run with custom number of days
php artisan admin:activity-summary 30

# Examples
php artisan admin:activity-summary 1   # Yesterday's activity
php artisan admin:activity-summary 14  # Last 2 weeks
php artisan admin:activity-summary 90  # Last 3 months
```

### Automated Schedule
The command is automatically scheduled to run:
- **Weekly**: Every Monday at 6:00 AM EST (7-day summary)
- **Monthly**: First day of each month at 6:00 AM EST (30-day summary)

To view all scheduled tasks:
```bash
php artisan schedule:list
```

## Activity Summary Includes

The email report includes counts and details for:

1. **Logins** - All user login activities
2. **Deletions** - All deleted content (Events, Entities, Series, Users, etc.)
3. **New Users** - Newly registered user accounts
4. **New Events** - Recently created events
5. **New Entities** - Recently created entities (venues, artists, promoters, etc.)
6. **New Series** - Recently created event series
7. **Other Activities** - All other tracked activities (limited to 20 items in email)

## Email Format

The email is formatted as a Markdown-based message that includes:
- Overview table with activity counts
- Detailed sections for each activity category
- Links to relevant resources (where applicable)
- Date range and generation timestamp

## Configuration

The command uses the following configuration values from `.env`:
- `APP_ADMIN_EMAIL` - Recipient of the summary email
- `APP_NOREPLY_EMAIL` - Sender email address
- `APP_NAME` - Site name used in email
- `APP_URL` - Base URL for links in email

## Example Output

When run from the command line, the command displays a summary table:

```
Generating activity summary for the past 7 days (2025-11-13 to 2025-11-20)

Activity Summary:
+-------------------+-------+
| Category          | Count |
+-------------------+-------+
| Logins            | 45    |
| Deletions         | 2     |
| New Users         | 5     |
| New Events        | 12    |
| New Entities      | 3     |
| New Series        | 1     |
| Other Activities  | 28    |
| Total             | 96    |
+-------------------+-------+

Activity summary email sent successfully to admin@example.com
```

## Logging

All command executions are logged to the Laravel log files:
- Success: `Admin activity summary email sent to {email} for the past {days} days.`
- Failure: `Failed to send admin activity summary email: {error message}`

## Testing

Run the test suite for this command:
```bash
php artisan test --filter AdminActivitySummaryTest
```

## Notes

- The command respects the timezone configured in the Console Kernel (default: America/New_York)
- Activities are pulled from the `activities` table with relationships to `actions` and `users`
- The "Other Activities" section in the email is limited to 20 items to prevent overly long emails
- The command will return exit code 0 on success and 1 on failure
- Invalid parameters (e.g., days < 1) will be rejected with an error message
