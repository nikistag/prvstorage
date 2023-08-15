@extends('layouts.app', ['title' => 'Shares'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span>% of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width:<?= $quota ?>%"></div>
    </div>
</div>
<div class="row">
    <div class="col s6 left-align">
        <a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
    </div>
    <div class="col s6 right-align">
        <a href="{{route('ushare.purge')}}" class="waves-effect waves-light btn-small right red accent-4"><i class="material-icons right">delete_forever</i>Purge all</a>
    </div>
</div>

<h4>Folders you shared with local users</h4>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Row</th>
            <th>User</th>
            <th>Path</th>
            <th>Expiration</th>
            <th>Edit</th>
            <th>Remove</th>
        </tr>
    </thead>
    <tbody>
        @if(count($localShares) == 0)
        <tr>
            <td colspan="6">There are no shares defined.!!!</td>
        </tr>
        @else
        @foreach($localShares as $ushare)
        <tr>
            <td>{{$loop->iteration}}</td>
            <td>{{$ushare->wuser->name}} / {{$ushare->wuser->email}} </td>
            <td><a href="{{route('folder.root', ['current_folder' => $ushare->shortPath])}}">{{$ushare->path}}</a></td>
            <td>{{$ushare->expirationDate}}</td>
            <td>
                <input type="hidden" id="user_{{$ushare->id}}" value="{{$ushare->wuser->id}}" />
                <input type="hidden" id="username_{{$ushare->id}}" value="{{$ushare->wuser->name}}{{' / '.$ushare->wuser->email}}" />
                <input type="hidden" id="expiration_{{$ushare->id}}" value="{{$ushare->expirationDate}}" />
                <input type="hidden" id="whichfolder_{{$ushare->id}}" value="{{$ushare->path}}" />
                <input type="hidden" id="route_{{$ushare->id}}" value="{{route('ushare.update', ['ushare' => $ushare->id])}}" />
                <a href="" id="{{$ushare->id}}" class="edit-share tooltipped" data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
            </td>
            <td>
                <a href="{{$ushare->id}}" class="modal-trigger remove-share tooltipped" data-target="modalremoveshare" data-tooltip="Delete" data-data="{{$ushare->path}}"><i class="material-icons red-text">remove_circle</i></a>
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
<!-- Edit share modal -->
<div id="pickerContainer"></div>
<div id="modaleditshare" class="modal">
    <form id="editshareform" method="POST" action="">
        <div class="modal-content">
            <h5>Update share with user</h5>
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col s12">
                    <i>Folder to share:</i>
                    <strong><i><span id="showFolderToShare"></span></i></strong>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <i>User to share with:</i>
                    <strong><i><span id="showUserToShare"></span></i></strong>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="expiration" name="expiration" type="text" class="datepicker">
                    <label for="first_name">Available till:</label>
                </div>
            </div>
            <div class="modal-footer" id="multifilefooter">
                <button id="submit-share-file" class="btn-small waves-effect waves-light" type="submit" name="action">Modify
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-share-file-modal" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>
<!-- Remove share modal -->
<div id="modalremoveshare" class="modal">
    <form method="POST" action="{{ route('ushare.delete') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to stop sharing this?</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <span class="sharetoremove"></span>
                        <input id="shareidtodelete" name="shareidtodelete" type="hidden" class="valid" value="" size="40" />
                        <label for="shareidtodelete"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.modal').modal();
        $('#modaleditshare').modal({
            dismissible: false,
        });
        $('.datepicker').datepicker({
            container: $('#pickerContainer'),
        });
        /* Open modal to edit share with the wild */
        $('.edit-share').on("click", (function(e) {
            e.preventDefault();
            //Getting data for share
            var updateForm = document.getElementById('editshareform');
            var shareId = $(this).attr('id');
            var user = document.getElementById('username_' + shareId);
            var expiration = document.getElementById('expiration_' + shareId);
            var folder = document.getElementById('whichfolder_' + shareId);
            var route = document.getElementById('route_' + shareId);
            //Update form elements
            var showUserToShare = document.getElementById('showUserToShare');
            var showFolder = document.getElementById('showFolderToShare');
            var showExpiration = document.getElementById('expiration');
            //Set default values for modal/form
            showUserToShare.appendChild(document.createTextNode(user.value));
            showFolder.appendChild(document.createTextNode(folder.value));
            showExpiration.value = expiration.value;
            updateForm.action = route.value;
            M.updateTextFields();
            //Open modal            
            var editmodal = document.getElementById('modaleditshare');
            var instance = M.Modal.getInstance(editmodal);
            instance.open();

        }));
        //Reset update form
        $('#close-share-file-modal').on("click", (function(e) {
            var updateForm = document.getElementById('editshareform');
            var showUserToShareForm = document.getElementById('showUserToShare');
            var showFolderForm = document.getElementById('showFolderToShare');
            var expirationForm = document.getElementById('expiration');
            updateForm.action = "";
            if (showFolderForm.hasChildNodes()) {
                showFolderForm.removeChild(showFolderForm.firstChild);
            }
            if (showUserToShareForm.hasChildNodes()) {
                showUserToShareForm.removeChild(showUserToShareForm.firstChild);
            }
            expirationForm.value = "";

        }));
        //Remove 
        $('.remove-share').on("click", (function(e) {
            e.preventDefault();
            var shareid = $(this).attr('href');
            var sharepath = $(this).attr('data-data');
            $('input[name=shareidtodelete]').val(shareid);
            $('.sharetoremove').html(sharepath);
        }));

        $('.clipboard-copy').on('click', (function(e) {
            e.preventDefault();
            var shareId = $(this).attr('data-custom');
            var sharelink = document.getElementById('link_' + shareId);
            var linkArea = document.createElement("textarea");
            linkArea.style.position = 'fixed';
            linkArea.style.top = 0;
            linkArea.style.left = 0;
            linkArea.style.width = '2em';
            linkArea.style.height = '2em';
            linkArea.style.padding = 0;
            linkArea.style.border = 'none';
            linkArea.style.outline = 'none';
            linkArea.style.boxShadow = 'none';
            linkArea.style.background = 'transparent';
            linkArea.value = sharelink.value;

            document.body.appendChild(linkArea);
            linkArea.select();
            linkArea.setSelectionRange(0, 99999);
            document.execCommand('copy');
            document.body.removeChild(linkArea);
            M.toast({
                html: "Copied download link: " + sharelink.value
            });
        }));

    });
</script>


@endsection