@extends('layouts.app', ['title' => 'My private storage'])

@section('content')

<div class="row">

    <h4>Statistics</h4>
    <h6>NOTICE!!! Free disk space may be a bit smaller due to operating system/ app needs.</h6>
    <p>Total disk space: {{ $disk_total_space }} Gb</p>
    <p>Free disk space: {{ $disk_free_space }} Gb</p>
    <div><span>{{$quota}}%</span></div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
    
    <a href="{{route('folder.root', ['current_folder' => '/'. auth()->user()->name])}}" class="btn-small waves-effect waves-light">My private files & folders<i class="material-icons right">cloud</i></a>

</div>

@endsection