@extends('layouts.app', ['title' => 'Home'])

@section('content')

<div class="row">

    @auth
    <h4>Let's get to your private storage</h4>
    <a href="{{route('folder.index')}}" class="btn-small waves-effect waves-light">My private storage<i class="material-icons right">cloud</i></a>

    @endauth
    @guest

    <h4>Please authenticate!!!</h4>

    <h5><a href="{{route('login')}}"><span class="blue-text">Login</span></a> or, If new account, <a href="{{route('register')}}"><span class="blue-text">Register</span></a></h5>

    @endguest
</div>

@endsection