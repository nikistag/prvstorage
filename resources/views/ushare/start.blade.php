@extends('layouts.app', ['title' => 'Shared by users'])

@section('content')

@include('partials._quota')

<div class="row">
    <div class="col s6 left-align">
        <a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i
                class="material-icons left">arrow_back</i>Back home</a>
    </div>
    <div class="col s6 right-align">
    </div>
</div>

@include('ushare._breadcrumbs')

@if(count($usershares) == 0)
<div class="row">
    <div class="col s6 left-align">
        <a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i
                class="material-icons left">arrow_back</i>Back home</a>
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
        <a href="{{route('ushare.root', ['current_folder' => '/UShare/'.$ushare->user->name])}}" class="valign-wrapper">
            <i class="material-icons large purple-text">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge"
            data-badge-caption="{{count($ushares->where('user_id', $ushare->user_id))}} folders"></span>
    </div>
    <div class="col s4 right-align">
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('ushare.root', ['current_folder' => '/UShare/'.$ushare->user->name])}}" class="valign-wrapper">
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
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i
                class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        <p>Click / tap <b><i>folder icon</i> <u>to expand</u></b><br /> Click / tap <b><i>folder name</i> <u>to
                    select</u></b></p>
        <ul id="treeView" class="browser-default left-align">
            {!! $folderTreeView !!}
        </ul>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i
                class="material-icons white-text">close</i></a>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.tooltipped').tooltip();
        $('.modal').modal();
        $('select').formSelect();
        $('#folder-tree-view').sidenav({
            edge: 'left'
        });
        /** Folder tree expanding */
        var toggler = document.getElementsByClassName("folder-tree");
        var i;
        for (i = 0; i < toggler.length; i++) {
            toggler[i].addEventListener("click", function () {
                this.parentElement.querySelector(".nested").classList.toggle("active-tree");
                this.classList.toggle("folder-tree-down");
            });
        }
        /** Share folder tree expanding */
        var toggler = document.getElementsByClassName("folder-tree-ushare");
        var i;
        for (i = 0; i < toggler.length; i++) {
            toggler[i].addEventListener("click", function () {
                this.parentElement.querySelector(".nested-ushare").classList.toggle("active-tree-ushare");
                this.classList.toggle("folder-tree-ushare-down");
            });
        }
    });


</script>

@endsection