<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>
<ul>
    @foreach($users as $user)
        <li>
            [{!! $user['spam_type'] !!}] - {!! $user['full_name'] !!} ({!! $user['email'] !!}) <a href="{!! $user['edit_link'] !!}" target="_blank">Edit</a>
        </li>
    @endforeach
</ul>
</p>
<br/>
<br/>
<p>Cheers,<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>