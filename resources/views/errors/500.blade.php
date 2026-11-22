{{--
    Self-contained on purpose: no layout, no database, no built assets. Whatever
    broke the request (the database, a missing build) would break a page that
    needed them too, and the visitor would get the bare framework error instead.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>500 - Something went wrong | {{ config('app.app_name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #111827; color: #f3f4f6; text-align: center; padding: 16px; }
        h1 { font-size: 3.75rem; margin: 0 0 16px; }
        p { color: #9ca3af; max-width: 28rem; margin: 0 auto 24px; line-height: 1.5; }
        p.lead { font-size: 1.25rem; }
        a { display: inline-block; padding: 8px 16px; border-radius: 6px; background: #f3f4f6; color: #111827; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <main>
        <h1>500</h1>
        <p class="lead">Something went wrong</p>
        <p>The problem is on our side and has been recorded. Please try again in a moment.</p>
        <a href="{{ url('/') }}">Home</a>
    </main>
</body>
</html>
