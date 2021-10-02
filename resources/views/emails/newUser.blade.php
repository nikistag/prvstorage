<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Prvstorage</title>
</head>

<body>
    <h3>New user require activation</h3>
    <p>User <strong>{{ $details['user_name'] }}</strong> with email: <strong>{{$details['user_email']}}</strong> has just registered on <strong>Prvstorage</strong></p>
    <p>To activate account and allow access to <strong>PRVSTORAGE</strong> click here <a href="{{route('user.edit', ['user' => $details['user_id']])}}">Activate/Edit</a></p>

    <p>Thank you</p>
</body>

</html>