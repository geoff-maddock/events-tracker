@component('mail::message')

# Your Data Export is Ready

Hello {{ $user->name }},

Your data export from {{ $site }} has been successfully generated and is ready for download.

@component('mail::button', ['url' => $downloadUrl])
Download Your Data
@endcomponent

**File:** {{ $filename }}

**Important Notes:**
- This download link will be available for 7 days
- The export includes all your contributed data in JSON format
- Photo images are not included to keep file size manageable (only metadata is included)
- The file is a ZIP archive containing:
  - Events you created
  - Event series you created
  - Entities you created
  - Posts and comments
  - Your follows (tags, entities, series, threads)
  - Event responses
  - Your profile information
  - Photo metadata (paths and details, but not the actual image files)
  - Reference data (event types, statuses, etc.)

If you have any questions or need assistance, please contact us at {{ $admin_email }}.

Thanks,<br>
{{ $site }}

@component('mail::subcopy')
If you're having trouble clicking the "Download Your Data" button, copy and paste the URL below into your web browser:
[{{ $downloadUrl }}]({{ $downloadUrl }})

This link will expire in 7 days for security purposes.
@endcomponent

@endcomponent
