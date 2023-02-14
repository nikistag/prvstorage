@extends('layouts.app', ['title' => 'Home'])

@section('content')

<div class="row">

    @auth
    <h4>Statistics</h4>
    <h6>NOTICE!!! Free disk space may be a bit smaller due to operating system/ app needs.</h6>
    <p>Total disk space: {{ $disk_total_space }} Gb</p>
    <p>Free disk space: {{ $disk_free_space }} Gb</p>
    <div><span>{{$quota}}%</span></div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
    
    <a href="{{route('folder.root', ['current_folder' => ''])}}" class="btn-small waves-effect waves-light">My private files & folders<i class="material-icons right">cloud</i></a>

    @endauth
    @guest

    <h4>Please authenticate!!!</h4>

    <h5><a href="{{route('login')}}"><span class="blue-text">Login</span></a> or, If new account, <a href="{{route('register')}}"><span class="blue-text">Register</span></a></h5>

    @endguest
</div>

@endsection