@extends('layouts.app', ['title' => 'Root folder'])

@section('content')

@include('partials._quota')

<div class="row">
    <form id="searchform" method="POST" action="{{ route('folder.search') }}">
        <div class="modal-content">
            <h5>Search files or folders</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="searchstring" name="searchstring" type="text" class="valid" value="" size="40" />
                        <input type="hidden" name="current_folder" value="{{$current_folder}}" />
                        <label for="searchstring"></label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@include('folder._breadcrumbs')

<div id="searchResults">

    @include('folder._folderView')

    @include('folder._fileView')

</div>
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
            {!!$folderTreeView!!}
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
        $('.modalupload').modal({
            dismissible: false,
        });
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
        //Ajax search for files and folders
        $('#searchstring').on("keyup", (function (e) {
            e.preventDefault();
            $.ajax({
                url: $('#searchform').attr("action"),
                type: "POST",
                data: {
                    '_token': $('input[name=_token]').val(),
                    'searchstring': $('input[name=searchstring]').val(),
                    'current_folder': $('input[name=current_folder]').val(),
                },
                success: function (data) {
                    if (typeof data.html !== "undefined") {
                        $('#searchResults').html(data.html);
                    }
                }
            });
        }));
        //Prevent search form from submitting input
        $('#searchform').on("submit", (function (e) {
            e.preventDefault();

        }));
    });
</script>
@endsection