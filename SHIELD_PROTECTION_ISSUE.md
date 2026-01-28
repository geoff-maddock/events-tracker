# Shield Protection Issue on /events Route

## Problem
When accessing https://dev.arcane.city/events, users are prompted with an HTTP Basic Auth popup asking for username/password.

## Investigation Results

### Code Analysis
After thorough investigation of the Laravel application code:

1. **Shield middleware is NOT applied globally**
   - Not in `$middleware` array (global middleware)
   - Not in `$middlewareGroups` (web/api middleware groups)
   - Only registered as route middleware alias in `Kernel.php`

2. **Shield is NOT applied to web routes**
   - No `->middleware('shield')` on `/events` route
   - No Shield middleware in `EventsController`
   - `routes/web.php` has no Shield references

3. **Shield is intended for API use only**
   - README.md documents Shield under "API Configuration"
   - `routes/api.php` has empty Shield route group (currently protecting nothing)

### Root Cause
The HTTP Basic Auth popup is **NOT** originating from the Laravel application code. Possible causes:

1. **Web Server Configuration (Most Likely)**
   - nginx or Apache may have Basic Auth configured for dev.arcane.city subdomain
   - Check nginx.conf or Apache .htaccess for `auth_basic` directives

2. **Shield Package Auto-Protection**
   - Shield package may auto-enable when `SHIELD_USER` and `SHIELD_PASSWORD` environment variables are set
   - This would be a package-level behavior, not application code

## Solutions

### Solution 1: Remove Web Server Basic Auth (Recommended)
If nginx/Apache has Basic Auth configured:

**For nginx:**
```nginx
# Remove or comment out these lines in your server block
# auth_basic "Restricted";
# auth_basic_user_file /etc/nginx/.htpasswd;
```

**For Apache:**
```apache
# Remove or comment out these lines in .htaccess or VirtualHost
# AuthType Basic
# AuthName "Restricted Access"
# AuthUserFile /path/to/.htpasswd
# Require valid-user
```

### Solution 2: Clear Shield Environment Variables
If Shield is auto-protecting based on environment:

1. Edit `.env` file on dev server
2. Remove or comment out:
   ```
   #SHIELD_USER=
   #SHIELD_PASSWORD=
   ```
3. Clear config cache: `php artisan config:clear`
4. Restart web server

### Solution 3: Keep Shield for API Only
If you want to keep Shield for protecting specific API endpoints:

1. Ensure `.env` has Shield credentials
2. Apply Shield middleware ONLY to specific API routes:
   ```php
   // In routes/api.php
   Route::middleware('shield')->group(function () {
       Route::get('protected/endpoint', ...);
   });
   ```
3. **Never** add Shield to global middleware or web middleware group
4. Ensure web server doesn't have Basic Auth enabled

## Verification
After implementing the fix:

1. Clear browser cache
2. Access https://dev.arcane.city/events
3. Should load without authentication popup
4. API endpoints with Shield middleware should still require authentication

## Related Files
- `config/shield.php` - Shield configuration (credentials only)
- `app/Http/Kernel.php` - Middleware registration
- `routes/web.php` - Web routes (Shield not applied)
- `routes/api.php` - API routes (Shield group exists but empty)
