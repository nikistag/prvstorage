@extends('layouts.app', ['title' => 'Shared by users'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span> % of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width:<?= $quota ?>%;"></div>
    </div>
</div>

<div class="row">
    <div class="col s6 left-align">
        <a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
    </div>
    <div class="col s6 right-align">
    </div>
</div>

@include('ushare._breadcrumbs')

@if(count($usershares) == 0)
<div class="row">
    <div class="col s6 left-align">
        <a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
    </div>
    <div class="col s6 right-align">
        No shares from other users
    </div>
</div>
@else
@foreach($usershares as $ushare)
<!-- New version -->
<div class="row">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('ushare.explore', ['userid' => $ushare->user_id])}}" class="valign-wrapper">
            <i class="material-icons large purple-text">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{count($shares->where('user_id', $ushare->user_id))}} folders"></span>
    </div>
    <div class="col s4 right-align">
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('ushare.explore', ['userid' => $ushare->user_id])}}" class="valign-wrapper">
            <span>Shared by <b>{{$ushare->user->name}}</b>-<i>{{$ushare->user->email}}</i></span>
            <br>
        </a>
    </div>
</div>
@endforeach
@endif
<!-- Directory tree modal -->
<div id="directoryTreeModal" class="modal">
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        <p>Click / tap <b><i>folder icon</i> <u>to expand</u></b><br /> Click / tap <b><i>folder name</i> <u>to select</u></b></p>
        <ul id="treeView" class="browser-default left-align">
            {!! $folderTreeView ?? ''!!}
        </ul>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
</div>
@endsection