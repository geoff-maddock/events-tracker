# User Data Export Feature

## Overview

The User Data Export feature allows users to download all data they've contributed to the site in a transferable format. This includes events, series, entities, posts, comments, follows, photos, and profile information.

## How It Works

### For Users

1. Navigate to your user profile page
2. Click the "Export My Data" button
3. Confirm the export request
4. You'll receive an email with a download link when the export is ready (usually within a few minutes)
5. Download the ZIP file from the link in the email
6. The download link expires after 7 days

### What's Included in the Export

The export is a ZIP file containing:

#### JSON Files
- `events.json` - All events you created
- `series.json` - All event series you created
- `entities.json` - All entities (venues, artists, promoters) you created
- `posts.json` - All forum posts you created
- `comments.json` - All comments you created
- `blogs.json` - All blog posts you created (if applicable)
- `follows.json` - All entities, tags, series, and threads you follow
- `event_responses.json` - Your event responses (attending, interested, etc.)
- `profile.json` - Your profile information and settings
- `photos_metadata.json` - Metadata for all photos associated with your account
- `constants.json` - Reference data (event types, statuses, visibility options, etc.)

#### Photos Directory
- Original images and thumbnails for all photos associated with your account

## Technical Implementation

### Architecture

```
User clicks "Export My Data"
         ↓
Controller validates authorization
         ↓
Job queued (ExportUserDataJob)
         ↓
DataExportService aggregates all user data
         ↓
JSON files created with proper formatting
         ↓
Photos downloaded from storage
         ↓
ZIP archive created
         ↓
File stored temporarily
         ↓
Email sent with download link
         ↓
Cleanup job removes files after 7 days
```

### Key Components

#### DataExportService (`app/Services/DataExportService.php`)
- Aggregates all user data from database
- Transforms data using Laravel Resources for consistency
- Downloads photos from storage
- Creates ZIP archive
- Manages file cleanup

#### ExportUserDataJob (`app/Jobs/ExportUserDataJob.php`)
- Queued job for background processing
- Calls DataExportService to generate export
- Sends email notification when complete
- Handles errors gracefully

#### UserDataExportReady (`app/Mail/UserDataExportReady.php`)
- Email notification with download link
- Includes expiry information
- User-friendly formatting

#### CleanupExports Command (`app/Console/Commands/CleanupExports.php`)
- Scheduled daily at 3 AM
- Removes export files older than 7 days
- Configurable retention period

### Security Features

1. **Authorization**: Users can only export their own data (or superusers can export any user's data)
2. **UUID Filenames**: Export filenames use UUIDs to prevent user enumeration
3. **Temporary Storage**: Files are automatically cleaned up after 7 days
4. **Activity Logging**: All export requests are logged for auditing
5. **CSRF Protection**: Form submission requires valid CSRF token

### Data Transformation

All data is transformed using Laravel's JsonResource classes to ensure:
- Consistent data structure
- Proper relationship handling
- Removal of sensitive internal data
- Human-readable formats

### File Storage

- Exports are stored in `storage/app/exports/`
- Public downloads available at `storage/app/public/exports/`
- Both directories are excluded from version control

**Note**: For production deployments using S3 or DigitalOcean Spaces, the `getDownloadUrl()` method should be updated to use `Storage::temporaryUrl()` for signed URLs with automatic expiration.

## Installation & Configuration

### Required Setup

1. Ensure queue system is configured in `.env`:
   ```
   QUEUE_CONNECTION=database
   ```

2. Run migrations and seeders:
   ```bash
   php artisan migrate
   php artisan db:seed --class=ActionsTableSeeder
   ```

3. Create storage directories:
   ```bash
   mkdir -p storage/app/exports
   mkdir -p storage/app/public/exports
   ```

4. Link public storage (if not already done):
   ```bash
   php artisan storage:link
   ```

### Running the Queue Worker

For local development:
```bash
php artisan queue:work
```

For production, use a process manager like Supervisor to keep the queue worker running.

### Scheduled Tasks

Add to your crontab (or server scheduler):
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This will automatically run:
- Daily cleanup of old exports (3 AM)
- Other scheduled tasks

### Manual Cleanup

To manually clean up old export files:
```bash
php artisan cleanup:exports
# Or with custom retention period
php artisan cleanup:exports --days=14
```

## Usage Examples

### Triggering an Export

Users can trigger exports from their profile page. The export button is visible when:
- User is viewing their own profile
- User is a superuser viewing any profile

### Monitoring Exports

Check logs for export activity:
```bash
tail -f storage/logs/laravel.log | grep "data export"
```

Activity logs in the database also track export requests.

## Troubleshooting

### Export Not Generating

1. Check queue is running: `php artisan queue:work`
2. Check failed jobs: `php artisan queue:failed`
3. Review logs: `storage/logs/laravel.log`

### Email Not Received

1. Verify email configuration in `.env`
2. Check mail logs
3. Check spam folder

### Photos Not Included

1. Verify storage configuration
2. Check file permissions on storage directories
3. Review logs for "Failed to download photo" warnings

## Future Enhancements

Potential improvements for future releases:

1. **Signed URLs**: Implement `Storage::temporaryUrl()` for S3/Spaces deployments
2. **Export History**: Track previous exports in user's profile
3. **Selective Export**: Allow users to choose what data to export
4. **Format Options**: Support additional formats (CSV, XML, etc.)
5. **Scheduled Exports**: Allow users to schedule automatic exports
6. **Data Portability**: Implement OAuth for direct import to other services

## API Endpoint

Currently, the export feature is only available through the web interface. Future versions may include an API endpoint:

```
POST /api/users/{id}/export
Authorization: Bearer {token}
```

## Compliance

This feature helps with:
- **GDPR Article 20**: Right to data portability
- **CCPA**: Consumer right to know and data portability
- **User Privacy**: Transparency about collected data

## Support

For issues or questions, please contact the site administrator or open an issue on the GitHub repository.
