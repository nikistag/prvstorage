@extends('layouts.app', ['title' => 'Admins'])

@section('content')

<a href="{{route('folder.root')}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h4>Administrators</h4>

<ul class="collection">
    @foreach($admins as $admin)
    <li class="collection-item">
        <strong>{{$loop->iteration}}.</strong> 
        username: <strong><span class="indigo-text">{{$admin->name}}</strong></span> 
        e-mail: <strong><span class="indigo-text">{{$admin->email}}</strong></span>
    </li>
    @endforeach
</ul>

@endsection