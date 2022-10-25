@extends('layouts.app', ['title' => 'Root folder'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span> % of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
</div>
<div class="row">
    @if($current_folder != "/ZTemp")
    <div class="hide-on-small-only">
        <div class="col s12 center blue-grey lighten-4">
            <a href="Multiple files">
                <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modalfilesupload" data-position="bottom" data-tooltip="Upload multiple files" onclick="jsUpload('multiupload','filesupload','file-list-display')">
                    playlist_add
                </i>
            </a>
            <a href="New Folder">
                <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modal1" data-position="bottom" data-tooltip="Create new folder here">
                    folder_open
                </i>
            </a>
            <a href="Folder upload">
                <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modalfolderupload" data-position="bottom" data-tooltip="Upload folder" onclick="jsUpload('folderuploadform','folderupload','folder-list-display')">
                    create_new_folder
                </i>
            </a>
            <a href="{{route('folder.searchForm', ['current_folder' => $current_folder])}}">
                <i class="material-icons medium purple-text tooltipped modal-trigger" data-position="bottom" data-tooltip="Search file/folder">
                    search
                </i>
            </a>
        </div>
    </div>
    <div class="hide-on-med-and-up">
        <div class="col s12 center blue-grey lighten-4">
            <a href="Multiple files">
                <i class="material-icons medium purple-text modal-trigger" data-target="modalfilesupload" data-position="bottom" onclick="jsUpload('multiupload','filesupload','file-list-display')">
                    playlist_add
                </i>
            </a>
            <a href="New Folder">
                <i class="material-icons medium purple-text modal-trigger" data-target="modal1" data-position="bottom">
                    folder_open
                </i>
            </a>
            <a href="{{route('folder.searchForm', ['current_folder' => $current_folder])}}">
                <i class="material-icons medium purple-text modal-trigger" data-position="bottom">
                    search
                </i>
            </a>
        </div>
    </div>
    @endif
</div>

@include('folder._breadcrumbs')

@include('folder._folderView')

@include('folder._fileView')


<!-- Directory tree modal -->
<div id="directoryTreeModal" class="modal">
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        {!!$folderTreeView!!}
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
</div>

<!-- Preparing data modal -->
<div id="modalbgworking" class="modal">
    <div class="modal-content">
        <h5 class="red-text" id="preparing"></h5>
    </div>
    <div class="modal-footer">
        <div class="progress">
            <div class="indeterminate"></div>
        </div>
    </div>
    <!-- Form used to initiate file share -->
    <form method="POST" id="fileshareform" action="{{route('share.file')}}">
        @csrf
        <input id="fileshareinput" type="hidden" name="fileshare" value="">
    </form>
    <!-- Form used to initiate multiple files share -->
    <form method="POST" id="multifileshareform" action="{{route('share.fileMulti')}}">
        @csrf
        <input id="path" type="hidden" name="path" value="{{$path}}">
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
        $('.modal').modal();
        $('.modalupload').modal({
            dismissible: false,
        });
        $('select').formSelect();
        $('#folder-tree-view').sidenav({
            edge: 'left'
        });
    });

    function jsUpload(form, input, display) {
        var fileCatcher = document.getElementById(form); //form
        var fileInput = document.getElementById(input); //input        
        var fileListDisplay = document.getElementById(display); //display
        var fileList = [];
        var renderFileList, sendFile;

        //personal addings
        var submitButton = document.getElementById('submit-files-upload');
        var closeButton = document.getElementById('close-upload-modal');

        fileCatcher.addEventListener('submit', function(evnt) {
            evnt.preventDefault();
            closeButton.classList.add("hide"); //my
            submitButton.classList.add("hide"); //my
            fileList.forEach(function(file) {
                sendFile(file);
            });
        });

        fileInput.addEventListener('change', function(evnt) {
            fileList = [];
            var clientFiles = Array.from(fileInput.files);
            const result = Array.isArray(clientFiles) ? clientFiles.sort(function(a, b) {
                return a.size - b.size
            }) : [];
            console.log(clientFiles);
            for (var i = 0; i < fileInput.files.length; i++) {
                fileList.push(clientFiles[i]);
            }
            renderFileList();
        });

        renderFileList = function() {
            fileListDisplay.innerHTML = '';
            fileList.forEach(function(file) { //added index
                var fileDisplayEl = document.createElement("div");
                fileDisplayEl.setAttribute("class", "collection-item notuploaded");
                fileDisplayEl.setAttribute("id", file.webkitRelativePath + file.name + file.size + "item");
                fileDisplayEl.innerHTML = file.name;
                fileListDisplay.appendChild(fileDisplayEl);
                var progressDisplayEl = document.createElement("div");
                progressDisplayEl.setAttribute("class", "progress");
                progressDisplayEl.setAttribute("id", file.webkitRelativePath + file.name + file.size + "progress");
                progressDisplayEl.innerHTML = "";
                fileDisplayEl.appendChild(progressDisplayEl);
                var progressBarEl = document.createElement("div");
                progressBarEl.setAttribute("class", "determinate");
                progressBarEl.setAttribute("id", file.webkitRelativePath + file.name + file.size);
                progressBarEl.setAttribute("style", "width:1%");
                progressBarEl.innerHTML = "";
                progressDisplayEl.appendChild(progressBarEl);
                console.log(file.webkitRelativePath + file.name + file.size);
            });
        };

        sendFile = function(file) {
            var formData = new FormData();
            var request = new XMLHttpRequest();

            formData.set('file', file);
            formData.set('_token', $('input[name=_token]').val());
            formData.set('filepath', file.webkitRelativePath);
            formData.set('current_folder', $('input[name=current_folder]').val());

            request.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    var progressBar = document.getElementById(file.webkitRelativePath + file.name + file.size);
                    var progresspc = Math.round(evt.loaded * 100 / evt.total);
                    var handbreak = 0;
                    progressBar.style.width = progresspc + "%";
                    if (progresspc == 100) {
                        console.log("Saved one file!");
                        M.toast({
                            html: 'Saving ' + file.name + ' to server!'
                        });
                    }
                }
            }, false);

            request.onloadend = function() {
                if (this.status == 200) {
                    var uploadItem = document.getElementById(file.webkitRelativePath + file.name + file.size + "item");
                    uploadItem.classList.remove("notuploaded");
                    uploadItem.classList.add("hide");
                    var notdone = document.getElementsByClassName("notuploaded").length;
                    if (notdone > 0) {
                        console.log("Files still in queue");
                    } else {
                        setTimeout(function() {
                            window.location.reload(true);
                        }, 2000);
                    }
                } else {
                    console.log("error " + this.status);
                }
            };
            request.open("POST", $("#" + form).attr("action"));
            request.send(formData);
        };
    };
</script>
@endsection