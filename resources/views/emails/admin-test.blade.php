<html>
<body>
Hello Admin User!

This is a test notification sent via AdminTest command which is scheduled in Kernel.php.

It was created at
{{ Carbon\Carbon::now()->toDateTimeString() }}

<P></P>
Thanks!<br>
{{ $url }}
</body>
</html>
