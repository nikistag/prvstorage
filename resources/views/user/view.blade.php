@extends('layouts.app', ['title' => 'Account info'])

@section('content')

<div class="row">
    <div class="col s12">
        <a href="{{route('home')}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
    </div>
</div>

<h4>Your account</h4>

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
    <li>
        <a href="" class="modal-trigger tooltipped waves-effect waves-light btn-small right red accent-4" data-target="modalpurgeaccount" data-tooltip="Delete account">
            <i class="material-icons right">delete_forever</i>
            Purge account
        </a>
    </li>
</ul>


<!-- Purge account modal -->
<div id="modalpurgeaccount" class="modal">
    <form id="purgeform" method="POST" action="{{route('user.purge')}}">
        <div class="modal-content">
            <h5 class="red-text text-accent-4">WARNING! Purging account cannot be undone! </h5>
            <h5 class="purple-text">Are you sure to purge your account?</h5>
            @csrf
            <div class="row">
                <div class="col s12 red-text">
                    <p><i>This action will delete your account and all files and folders stored by you in your private space. BEWARE! Files and folders moved/copied in <b>NShare</b> folder will not be deleted.</i></p>
                    <input id="userid" name="userid" type="hidden" class="valid" value="{{auth()->user()->id}}" />
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-small waves-effect waves-light" id="submitPurgeAccount" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
        $('.modal').modal();
    });
</script>



@endsection