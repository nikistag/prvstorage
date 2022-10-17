<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{env('APP_NAME')}}</title>
</head>

<body>
    <h3>Account status changed</h3>
    <p>User <strong>{{ $details['user_name'] }}</strong> with email: <strong>{{$details['user_email']}}</strong> {{$details['message']}}</p>
    @if($details['access'] == 1)
    <p><a href="{{route('home')}}">My private storage</a></p>
    @else
    <p><a href="{{route('user.admins')}}">Contact administrator</a></p>
    @endif
    <p>Thank you</p>
</body>

</html>