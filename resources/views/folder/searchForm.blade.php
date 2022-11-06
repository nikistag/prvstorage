@extends('layouts.app', ['title' => 'Root folder'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span> % of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
</div>
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
            //Ajax search for files and folders
            $('#searchstring').on("keyup", (function(e) {
                e.preventDefault();
                $.ajax({
                    url: $('#searchform').attr("action"),
                    type: "POST",
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'searchstring': $('input[name=searchstring]').val(),
                        'current_folder': $('input[name=current_folder]').val(),
                    },
                    success: function(data) {
                        if (typeof data.html !== "undefined") {
                            $('#searchResults').html(data.html);
                        }
                    }
                });
            }));
            //Prevent search form from submitting input
            $('#searchform').on("submit", (function(e) {
                e.preventDefault();
                
            }));
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

</div>

@endsection