@extends('layouts.app', ['title' => 'Root folder'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span> of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
</div>

<div class="row">
    <div class="col s12 center">
        <a href="New File">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modalfileupload" data-position="bottom" data-tooltip="Upload file to current folder">
                menu
            </i>
        </a>
        <a href="Multiple files">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modalfilesupload" data-position="bottom" data-tooltip="Upload multiple files">
                playlist_add
            </i>
        </a>
        <a href="New Folder">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modal1" data-position="bottom" data-tooltip="Create new folder here">
                folder_open
            </i>
        </a>


        <a href="New Folder">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modal1" data-position="bottom" data-tooltip="Upload folder">
                create_new_folder
            </i>
        </a>
    </div>

</div>
<nav class="blue-grey">
    <div class="nav-wrapper">
        <div class="col s12">
            Current folder:
            <a href="#!" class="breadcrumb"><span class="orange-text text-lighten-4">{{$current_folder == null ? "/" : $current_folder}}</span></a>
        </div>
    </div>
</nav>

<!-- If in subfolder print back button -->
@if($current_folder != null)
<div class="row">
    <div class="col s12">
        <a href="{{route('folder.root', ['current_folder' => $parent_folder])}}" class="left">
            <i class="material-icons blue-text">arrow_back</i>
        </a>
    </div>
</div>
@endif


@if(count($directories) == 0)
<p>You have no directories stored.</p>
@else

@foreach($directories as $directory)
<div class="row hoverable">
    <div class="col s6 valign-wrapper">
        @if($current_folder != null)
        <a href="{{route('folder.root', ['current_folder' => $current_folder.'/'.$directory])}}" class="valign-wrapper">
            <i class="material-icons orange-text">folder</i>
            {{$directory}}
        </a>
        @else
        <a href="{{route('folder.root', ['current_folder' => '/'.$directory])}}" class="valign-wrapper">
            <i class="material-icons orange-text">folder</i>
            {{$directory}}
        </a>
        @endif

    </div>
    <div class="col s6 right-align">
        <form action="route">
            @if($current_folder == null)
            <input id="$directory" name="directory" type="hidden" value="{{'app/prv/' . auth()->user()->name.'/'.$directory}}" />
            @else
            <input id="$directory" name="directory" type="hidden" value="{{'app/prv/' . auth()->user()->name.'/'.$current_folder.'/'.$directory}}" />
            @endif
            <a href="{{$directory}}" class="modal-trigger edit-folder" data-target="modaledit"><i class="material-icons blue-text">edit</i></a>
            <a href="{{$directory}}" class="modal-trigger move-folder" data-target="modalmove"><i class="material-icons orange-text">subdirectory_arrow_right</i></a>
            <a href="{{$directory}}" class="modal-trigger remove-folder" data-target="modalremove"><i class="material-icons red-text">remove_circle</i></a>
        </form>

    </div>
</div>
@endforeach

@endif
@if(count($files) == 0)
<p>You have no files stored in this directory.</p>
@else

@foreach($files as $file)
<div class="row hoverable">
    <div class="col s6 valign-wrapper">
        <i class="material-icons">description</i>
        {{$file}}
    </div>
    <div class="col s6 right-align">
        <form action="files">
            @if($current_folder == null)
            <input id="$file" name="file" type="hidden" value="{{'app/prv/' . auth()->user()->name.'/'.$file}}" />
            @else
            <input id="$file" name="file" type="hidden" value="{{'app/prv/' . auth()->user()->name.'/'.$current_folder.'/'.$file}}" />
            @endif
            <a href="{{$file}}" class="modal-trigger rename-file" data-target="modalrenamefile"><i class="material-icons blue-text">edit</i></a>
            <a href="{{$file}}" class="modal-trigger move-file" data-target="modalmovefile"><i class="material-icons orange-text">subdirectory_arrow_right</i></a>
            <a href="{{$file}}" class="modal-trigger remove-file" data-target="modalremovefile"><i class="material-icons red-text">remove_circle</i></a>
        </form>
    </div>
</div>

@endforeach
@endif
</div>



<!-- New folder modal -->
<div id="modal1" class="modal">
    <form method="POST" action="{{ route('folder.newfolder') }}">
        <div class="modal-content">
            <h5>New folder</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="newfolder" name="newfolder" type="text" class="valid" value="" size="40" />
                        <label for="newfolder">New folder</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Edit folder modal -->
<div id="modaledit" class="modal">
    <form method="POST" action="{{ route('folder.editfolder') }}">
        <div class="modal-content">
            <h5>Edit folder</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="editfolder" name="editfolder" type="text" class="valid" value="" size="40" />
                        <label for="editfolder"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldfolder" name="oldfolder" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Move folder modal -->
<div id="modalmove" class="modal">
    <form method="POST" action="{{ route('folder.moveFolder') }}">
        <div class="modal-content">
            <h5>Move folder</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="movefolder" name="movefolder" type="text" class="valid" value="" size="30" disabled />
                        <label for="movefolder"></label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <select id="target" name="target">
                        <option value="" disabled>Choose where</option>
                        @foreach($private_directory_paths as $path)
                        <option value="{{$path}}">{{$path}}</option>
                        @endforeach
                    </select>
                    <label>Choose folder</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldmovefolder" name="oldmovefolder" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Remove folder modal -->
<div id="modalremove" class="modal">
    <form method="POST" action="{{ route('folder.remove') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete this folder?</h5>
            @method('DELETE')
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <span class="foldertoremove"></span>
                        <input id="folder" name="folder" type="hidden" class="valid" value="" size="40" />
                        <label for="folder"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Rename file modal -->
<div id="modalrenamefile" class="modal">
    <form method="POST" action="{{ route('folder.renameFile') }}">
        <div class="modal-content">
            <h5>Rename file</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="renamefilename" name="renamefilename" type="text" class="valid" value="" size="40" />
                        <label for="renamefilename"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldrenamefilename" name="oldrenamefilename" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Move file modal -->
<div id="modalmovefile" class="modal">
    <form method="POST" action="{{ route('folder.moveFile') }}">
        <div class="modal-content">
            <h5>Move file</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="movefile" name="movefile" type="text" class="valid" value="" size="30" disabled />
                        <label for="movefile"></label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <select id="targetfolder" name="targetfolder">
                        <option value="" disabled>Choose where</option>
                        @foreach($private_directory_paths as $path)
                        <option value="{{$path}}">{{$path}}</option>
                        @endforeach
                    </select>
                    <label>Choose folder</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldfilefolder" name="oldfilefolder" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Remove file modal -->
<div id="modalremovefile" class="modal">
    <form method="POST" action="{{ route('folder.removeFile') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete this file?</h5>
            @method('DELETE')
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <span class="filetoremove"></span>
                        <input id="filename" name="filename" type="hidden" class="valid" value="" size="40" />
                        <label for="file"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- upload file modal -->
<div id="modalfileupload" class="modal">
    <form method="POST" action="{{ route('folder.fileupload') }}" enctype="multipart/form-data">
        <div class="modal-content">
            <h4>Pick file to upload</h4>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="file-field input-field">
                        <div class="btn">
                            <span>File</span>
                            <input id="fileupload" name="fileupload" type="file" class="valid" />
                        </div>
                        <div class="file-path-wrapper">
                            <input class="file-path validate" type="text">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- upload files modal -->
<div id="modalfilesupload" class="modal">
    <form id="multiupload" method="POST" action="{{ route('folder.multiupload') }}" enctype="multipart/form-data">
        <div class="modal-content">
            <h5>Pick multiple files to upload</h5>
            @csrf
            <input type="hidden" id="current_folder" name="current_folder" value="{{$current_folder}}" />
            <div class="row">
                <div class="col s12">
                    <div class="file-field input-field">
                        <div class="btn">
                            <span>Files</span>
                            <input id="filesupload" name="files[]" type="file" class="valid" multiple />
                        </div>
                        <div class="file-path-wrapper">
                            <input class="file-path validate" type="text">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" id="multifilefooter">
            <button id="submitupload" class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
    <div class="collection" id='file-list-display'></div>
</div>



<!-- Ajax routes -->
<!-- <input type="hidden" name="newfolderroute" value="route" /> -->
<!-- Ajax additional data -->
@csrf
<!-- <input type="hidden" name="additionaldata" value="additionaldata" /> -->
<!-- <input type="hidden" name="additionaldata" value="additionaldata" /> -->


<script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
        $('.modal').modal();
        $('select').formSelect();

        $('.edit-folder').on("click", (function(e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=editfolder]').val(foldername);
            $('input[name=oldfolder]').val(foldername);

        }));
        $('.move-folder').on("click", (function(e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=movefolder]').val(foldername);
            $('input[name=oldmovefolder]').val(foldername);

        }));
        $('.remove-folder').on("click", (function(e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=folder]').val(foldername);
            $('.foldertoremove').html(foldername);

        }));
        $('.rename-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=renamefilename]').val(filename);
            $('input[name=oldrenamefilename]').val(filename);

        }));
        $('.move-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=movefile]').val(filename);
            $('input[name=oldfilefolder]').val(filename);

        }));
        $('.remove-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=filename]').val(filename);
            $('.filetoremove').html(filename);

        }));

        (function() {
            var fileCatcher = document.getElementById('multiupload'); //form
            var fileInput = document.getElementById('filesupload'); //input
            var fileListDisplay = document.getElementById('file-list-display'); //display

            var fileList = [];
            var renderFileList, sendFile;

            fileCatcher.addEventListener('submit', function(evnt) {
                evnt.preventDefault();
                var modalFooter = document.getElementById('multifilefooter');
                modalFooter.classList.add("hide");
                fileList.forEach(function(file) {
                    sendFile(file);
                });

            });

            fileInput.addEventListener('change', function(evnt) {
                fileList = [];
                for (var i = 0; i < fileInput.files.length; i++) {
                    fileList.push(fileInput.files[i]);
                }
                renderFileList();
            });

            renderFileList = function() {
                fileListDisplay.innerHTML = '';
                fileList.forEach(function(file) {
                    var fileDisplayEl = document.createElement("div");
                    fileDisplayEl.setAttribute("class", "collection-item");
                    fileDisplayEl.setAttribute("id", file.size);
                    fileDisplayEl.innerHTML = file.name;
                    fileListDisplay.appendChild(fileDisplayEl);
                });
            };

            sendFile = function(file) {
                var formData = new FormData();
                var request = new XMLHttpRequest();

                formData.set('file', file);
                formData.set('_token', $('input[name=_token]').val());
                formData.set('current_folder', $('input[name=current_folder]').val());

                request.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var progressBar = document.getElementById(file.size);
                        var progresspc = Math.round(event.loaded * 100 / event.total);
                        progressBar.style = "background: linear-gradient(to left, gold " + (100 - progresspc) + "%, green " + progresspc + "%)";

                        // console.log("add upload event-listener" + evt.loaded + "/" + evt.total);
                    }
                }, false);
                // track upload progress
                /*  request.upload.onprogress = function(event) {
                     

                     console.log(`Uploaded ${event.loaded} of ${event.total}`);
                 }; */

                // track completion: both successful or not
                request.onloadend = function() {
                    if (request.status == 200) {
                        var progressBar = document.getElementById(file.size);
                        progressBar.style = "background-color: green";
                        console.log("success");
                    } else {
                        console.log("error " + this.status);
                    }
                };

                request.open("POST", $('#multiupload').attr("action"));
                request.send(formData);
            };
        })();

    });
</script>
@endsection