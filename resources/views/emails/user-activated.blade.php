<html>
<body>
<div>
    Hello {{ $user->name }},<br><br>
    We're writing to inform you that your account was <a>approved</a> and activated fully!<br><br>

    You can now make additions to the site by posting events, entities, series or on the forum.<br><br>

    Visit the site at <a href="{{ $url }}">{{ $url }}</a>

    <br><br>
    We're constantly adding new features, functionality and updates to improve your experience. <br>
    If you have any feedback, don't hesitate to drop us a line.
</div>

<P></P>
Thanks!<br>
{{ $site }}<br>
{{ $url }}
</body>
</html>