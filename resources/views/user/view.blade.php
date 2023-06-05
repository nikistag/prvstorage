@extends('layouts.app', ['title' => 'Admins'])

@section('content')

<a href="{{route('home')}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h4>Account information</h4>

<ul class="collection">
    <li class="collection-item">
        username: <strong><span class="indigo-text">{{$user->name}}</strong></span>
    </li>
    <li class="collection-item">
        e-mail: <strong><span class="indigo-text">{{$user->email}}</strong></span>
    </li>
    <li class="collection-item">
        superadmin: <strong><span class="indigo-text">{{$user->superadminRole}}</strong></span>
    </li>
    <li class="collection-item">
        admin: <strong><span class="indigo-text">{{$user->adminRole}}</strong></span>
    </li>
    <li class="collection-item">
        storage used: <strong><span class="indigo-text">{{$user->folderSize['size']}}&nbsp;{{$user->folderSize['type']}}</strong></span>
    </li>
</ul>

@endsection