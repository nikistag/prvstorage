@if(count($files) == 0)
<p>You have no files stored in this directory.</p>
@else

    @foreach($files as $file)

    <div class="row">
        <div class="col s4 left-align" style="position: relative;">
            @if($file['filevideourl'] === null)
            <!-- Image preview -->
            <img src="{{asset($file['fileimageurl'])}}" alt="file image">
            @else
            <!-- Video preview -->
            <video width="100" height="100" autoplay muted loop>
                <source src="{{asset($file['filevideourl'])}}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            @endif
            <div class="extension-text"><span class="new badge blue-grey" data-badge-caption="{{$file['extension']}}"></span></div>
        </div>
        <div class="col s4">
            <span class="new badge" data-badge-caption="{{ $file['filesize']['type']}}">{{ $file['filesize']['size']}}</span>
        </div>
        <div class="col s4 right-align">
            @if($current_folder == '/ZTemp')
            @else
            <a href="{{$file['fullfilename']}}" class="modal-trigger rename-file tooltipped" data-target="modalrenamefile" data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
            <a href="{{$file['fullfilename']}}" class="tooltipped sharelink" data-tooltip="Share"><i class="material-icons blue-text">share</i></a>
            <a href="{{$file['fullfilename']}}" class="modal-trigger share-file tooltipped" data-target="modalfileshare" data-tooltip="Share outside app"><i class="material-icons blue-text">share</i></a>
            <br />
            <a href="{{$file['fullfilename']}}" class="modal-trigger move-file-big tooltipped" data-target="modalmovefilebig" data-tooltip="Move/Copy"><i class="material-icons purple-text">content_copy</i></a>
            <a href="{{route('folder.filedownload', ['path' => $current_folder == null ? '/'.$file['fullfilename'] : $current_folder.'/'.$file['fullfilename']])}}" class="tooltipped" data-tooltip="Download"><i class="material-icons blue-text">cloud_download</i></a>
            <br />
            @if($file['filevideourl'] === null)
            @else
            <a href="{{route('folder.filestream', ['path' => $current_folder == null ? '/'.$file['fullfilename'] : $current_folder.'/'.$file['fullfilename']])}}" class="tooltipped" data-tooltip="Play"><i class="material-icons blue-text">play_arrow</i></a>
            @endif
            <a href="{{$file['fullfilename']}}" class="modal-trigger remove-file tooltipped" data-target="modalremovefile" data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
            @endif
        </div>
    </div>
    <div class="row" style="border-bottom: 1px solid gray;">
        <div class="col s12 left-align">
            <label>
                <input name="selectedFile" id="{{$file['fullfilename']}}" class="filescheck" value="{{$file['fullfilename']}}" type="checkbox" />
                <span>
                    <span class="hide-on-small-only grey-text text-darken-3">{{$file['fullfilename']}}</span>
                    <span class="hide-on-med-and-up tooltipped grey-text text-darken-3" data-tooltip="{{$file['fullfilename']}}">{{$file['shortfilename'] . $file['extension']}}</span>
                </span>
            </label>
        </div>
    </div>

    @endforeach

    @if($current_folder == '/ZTemp')
    @else

    <div class="row center left-align blue-grey lighten-4">
        <div class="selectedaction blue-grey lighten-4" id="selectedaction">
            &nbsp;
            <a href="#share" class="tooltipped sharelink-files" data-tooltip="Share"><i class="material-icons medium blue-text">share</i></a>
            &nbsp;
            <a href="#copy" class="move-files tooltipped" data-tooltip="Move/Copy"><i class="material-icons medium purple-text">content_copy</i></a>
            &nbsp;
            <a href="#download" class="tooltipped" data-tooltip="Download" id="zipNDownloadFiles"><i class="material-icons medium blue-text">cloud_download</i></a>
            &nbsp;
            <a href="#delete" class="modal-trigger remove-files-multi tooltipped" data-target="modalremovefilesmulti" data-tooltip="Delete"><i class="material-icons medium red-text">remove_circle</i></a>
            &nbsp;
        </div>
    </div>

    @endif
@endif

<!-- Form for downloading multiple files -->
<form action="{{route('folder.multifiledownload')}}" id="multifiledownloadform">
    <input type="hidden" id="multiZipFileName" name="multiZipFileName" value="" />
    <input type="hidden" id="currentFolderMultiDownload" name="currentFolderMultiDownload" value="{{$current_folder}}" />
</form>

<!-- Form for checking file readiness -->
<form action="{{route('folder.fileReadiness')}}" id="fileReadinessForm"></form>

<!-- MODALS FOR FILE MANIPULATION -->

<!-- Directory tree move file modal -->
<div id="treeMoveFileModal" class="modal">
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        {!!$treeMoveFile!!}
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
</div>
<!-- Move file BIG modal -->
<div id="modalmovefilebig" class="modal">
    <form method="POST" action="{{ route('folder.moveFileBig') }}" id="bigFileForm">
        <div class="modal-content">
            <h5>Move/Copy file</h5>
            @csrf
            <div class="row">
                <div class="col s12 centered">
                    <p>
                        <label>
                            <input type="checkbox" class="filled-in" name="filecopy" />
                            <span>Copy</span>
                        </label>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col s12 centered">
                    Move file:
                    <div class="input-field inline">
                        <input id="fileDisplay" name="fileDisplay" type="text" class="valid" value="" size="30" disabled />
                        <label for="fileDisplay"></label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12 centered">
                    To folder:
                    <div class="input-field inline">
                        <input id="viewWhereToFolder" type="text" class="valid treeMoveFileModalTrigger" value="" size="30" readonly />
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder_big" name="current_folder_big" value="{{$current_folder}}" />
            <input type="hidden" id="file_big" name="file_big" value="" />
            <input type="hidden" id="whereToFolder" name="whereToFolder" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action" id="copyBigFileSubmit">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>

    <div class="progress">
        <div class="determinate" style="width: 0%;" id="copyFileProgress"></div>
    </div>

</div>
<!-- Directory tree move multi modal -->
<div id="treeMoveMultiModal" class="modal">
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        {!!$treeMoveMulti!!}
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
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
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldrenamefilename" name="oldrenamefilename" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Move multiple files modal -->
<div id="modalmovefiles" class="modal">
    <form method="POST" action="{{ route('folder.moveFileMulti') }}" id="multiFileForm">
        <div class="modal-content">
            <h5>Move/Copy files</h5>
            @csrf
            <div class="row">
                <div class="col s12 centered">
                    <p>
                        <label>
                            <input type="checkbox" class="filled-in" name="filecopy" />
                            <span>Copy</span>
                        </label>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col s12 centered">
                    Move files:
                    <div class="input-field inline">
                        <input id="fileDisplayMulti" name="fileDisplayMulti" type="text" class="valid" value="" size="30" disabled />
                        <label for="fileDisplayMulti"></label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12 centered">
                    To folder:
                    <div class="input-field inline">
                        <input id="viewWhereToFolderMulti" type="text" class="valid treeMoveMultiModalTrigger" value="" size="30" readonly />
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="targetFolderSize" name="targetFolderSize" value="" />
            <input type="hidden" id="current_folder_multi" name="current_folder_multi" value="{{$current_folder}}" />
            <input type="hidden" id="targetfoldermulti" name="targetfoldermulti" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action" id="copyMultiFileSubmit">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>

    <ul class="collection" id='copyFilesDisplay'>
        <li class="collection-item" id="oneFileCopy">
            Copy/Move files progress
        </li>
        <div class="progress">
            <div class="determinate" style="width: 0%" id="multiFilesCopyProgress"></div>
        </div>
    </ul>

    <!-- Links used with jQuery to calculate progress bar for copy file/folders -->
    <form action="{{route('folder.multiFilesCopyProgress')}}" id="multiFilesCopyProgressForm"></form>
    <form action="{{route('folder.targetFolderSize')}}" id="targetFolderSizeForm"></form>

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
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>
<!-- Remove multifile modal -->
<div id="modalremovefilesmulti" class="modal">
    <form method="POST" action="{{ route('folder.removeFileMulti') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete these files?</h5>
            @method('DELETE')
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <span class="showfilestoremove"></span>
                        <input type="hidden" id="currentFolderDeleteMulti" name="current_folder" value="{{$current_folder}}" />
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

<!-- Upload files modal -->
<div id="modalfilesupload" class="modal modalupload">
    <form id="multiupload" method="POST" action="{{ route('folder.multiupload') }}" enctype="multipart/form-data">
        <div class="modal-content">
            <h5>Pick multiple files to upload</h5>
            @csrf
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <div class="row">
                <div class="col s12">
                    <div class="file-field input-field">
                        <div class="btn">
                            <span>Files</span>
                            <input id="filesupload" name="files[]" type="file" class="valid" multiple />
                        </div>
                        <div class="file-path-wrapper">
                            <input class="file-path validate" type="text" placeholder="Upload one or more files">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" id="multifilefooter">
            <button id="submit-files-upload" class="btn-small waves-effect waves-light" type="submit" name="action">Upload
                <i class="material-icons right">cloud_upload</i>
            </button>
            <a href="#!" id="close-upload-modal" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
    <div class="collection" id='file-list-display'></div>
</div>
<!-- Share file modal -->
<div id="testPickerContainer"></div>
<div id="modalfileshare" class="modal">
    <form id="fileshareform" method="POST" action="{{ route('share.file') }}">
        <div class="modal-content">
            <h5>Share file with "the wild"</h5>
            @csrf
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <div class="row">
                <div class="col s12">
                    <i>File to share:</i>
                    <strong><i><span id="showFileToShare"></span></i></strong>
                    <input type="hidden" name="fileToShareInput" id="fileToShareInput" value="" />
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <div class="switch">
                            <label>
                                With unlimited downloads
                                <input type="checkbox" name="unlimited" id="unlimited">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="expiration" name="expiration" type="text" class="datepicker">
                    <label for="first_name">Available till:</label>
                </div>
            </div>
            <div class="modal-footer" id="multifilefooter">
                <button id="submit-share-file" class="btn-small waves-effect waves-light" type="submit" name="action">Share
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-share-file-modal" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>
<!-- Share multi file modal -->
<div id="pickerContainer"></div>
<div id="modalfilemultishare" class="modal">
    <form id="filemultishareform" method="POST" action="{{ route('share.fileMulti') }}">
        <div class="modal-content">
            <h5>Share selected files with "the wild"</h5>
            @csrf
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <div class="row">
                <div class="col s12">
                    <i>File to share:</i>
                    <strong><i><span id="showFileMultiToShare"></span></i></strong>
                    <input type="hidden" name="fileMultiToShareInput" id="fileMultiToShareInput" value="" />
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <div class="switch">
                            <label>
                                With unlimited downloads
                                <input type="checkbox" name="unlimited" id="unlimited">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="expiration" name="expiration" type="text" class="datepicker">
                    <label for="first_name">Available till:</label>
                </div>
            </div>
            <div class="modal-footer" id="multifilefooter">
                <button id="submit-share-file" class="btn-small waves-effect waves-light" type="submit" name="action">Share
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-share-file-modal" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>
<!-- Form used to initiate file share -->
<!-- 
<form method="POST" id="fileshareform" action="{{route('share.file')}}">
    @csrf
    <input id="fileshareinput" type="hidden" name="fileshare" value="">
</form>

 -->
<!-- Form used to initiate multiple files share -->
<form method="POST" id="multifileshareform" action="{{route('share.fileMulti')}}">
    @csrf
    <input id="path" type="hidden" name="path" value="{{$path}}" />
</form>

<!-- SCRRIPTS FOR FILE MANIPULATION -->
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            container: $('#pickerContainer'),
        });
        $('#close-share-file-modal').on("click", (function(e) {
            $('#showFileToShare').html("");
            $('#fileToShareInput').val("");
            $('#expiration').val("");
            $('#unlimited').prop('checked', false);
        }));
        /* Open modal to share file with the wild */
        $('.share-file').on("click", (function(e) {
            e.preventDefault();
            var fileToShare = $(this).attr('href');
            $('#showFileToShare').html(fileToShare);
            $('#fileToShareInput').val('{{$path}}' + '/' + fileToShare);
        }));
        /** Submit file share form */
        $('#submit-share-file').on("click", (function(e) {
            e.preventDefault();
            var elem = document.getElementById('modalbgworking');
            var instance = M.Modal.getInstance(elem);
            instance.open();
            var forWhat = document.getElementById('preparing');
            forWhat.innerHTML = "Preparing share";
            document.getElementById('fileshareform').submit();
        }));

        /* Manage link to share file */
        $('.sharelink').on("click", (function(e) {
            e.preventDefault();
            var elem = document.getElementById('modalbgworking');
            var instance = M.Modal.getInstance(elem);
            instance.open();
            var forWhat = document.getElementById('preparing');
            forWhat.innerHTML = "Preparing share";
            var fileshareinput = document.getElementById('fileshareinput');
            fileshareinput.value = '{{$path}}' + '/' + $(this).attr('href');
            document.getElementById('fileshareform').submit();
        }));
        /* Manage link to share multiple files */
        $('.sharelink-files').on("click", (function(e) {
            e.preventDefault();
            if ($('input[name="selectedFile"]:checked').length == 0) {
                M.toast({
                    html: 'Nothing to do! No files selected'
                });
            } else {
                var elem = document.getElementById('modalbgworking');
                var instance = M.Modal.getInstance(elem);
                instance.open();
                var forWhat = document.getElementById('preparing');
                forWhat.innerHTML = "Preparing share";
                $('input[name="selectedFile"]:checked').each(function() {
                    var newInput = document.createElement("input");
                    newInput.type = "hidden";
                    newInput.name = "fileshare[]";
                    newInput.value = this.value;
                    var path = document.getElementById('path');
                    path.appendChild(newInput);
                });
                document.getElementById('multifileshareform').submit();
            }

        }));

        $('.rename-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=renamefilename]').val(filename);
            $('input[name=oldrenamefilename]').val(filename);

        }));
        /* Move/copy file mechanics */
        $('.move-file-big').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=fileDisplay]').val(filename);
            $('input[name=file_big]').val(filename);
            var viewFolderInput = document.getElementById('viewWhereToFolder');
            var jsFolderInput = document.getElementById('whereToFolder');
            jsFolderInput.value = "";
            viewFolderInput.value = "";
        }));
        $('.tree-move-file').on("click", (function(e) {
            /* Select target folder from folder tree */
            e.preventDefault();
            var target = $(this).attr('data-folder');
            var viewFolderInput = document.getElementById('viewWhereToFolder');
            var jsFolderInput = document.getElementById('whereToFolder');
            var moveFileModal = document.getElementById('treeMoveFileModal');
            var instance = M.Modal.getInstance(moveFileModal);
            instance.close();
            jsFolderInput.value = target;
            viewFolderInput.value = $(this).attr('data-folder-view');;
        }));
        $('#viewWhereToFolder').on("click", (function(e) {
            /* Open folder tree if disabled input clicked */
            e.preventDefault();
            var moveFileModal = document.getElementById('treeMoveFileModal');
            var instance = M.Modal.getInstance(moveFileModal);
            instance.open();
        }));


        $('#copyBigFileSubmit').on("click", (function(e) {
            e.preventDefault();
            $('#bigFileForm').submit();
            setInterval(function() {
                $.ajax({
                    url: $('#fileCopyProgressForm').attr("action"),
                    type: "POST",
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'targetfolder': $('select[name=targetfolderbig]').val(),
                        'copyfile': $('input[name=file_big]').val(),
                        'currentfolder': $('input[name=current_folder_big]').val(),
                    },
                    success: function(data) {
                        if (typeof data.progress !== "undefined") {
                            var progressBar = document.getElementById('copyFileProgress');
                            progressBar.style.width = data.progress + "%";
                        }
                    }
                });
            }, 2000);
        }));

        /* multiple files move / copy mechanics */
        $('.move-files').on("click", (function(e) {
            e.preventDefault();
            if ($('input[name="selectedFile"]:checked').length == 0) {
                M.toast({
                    html: 'Nothing to do! No files selected'
                });
            } else {
                var elem = document.getElementById('modalmovefiles');
                var instance = M.Modal.getInstance(elem);
                instance.open();
                var filename = '';
                $('input[name="selectedFile"]:checked').each(function() {
                    filename = filename + this.value + ', ';
                });
                $('input[name=fileDisplayMulti]').val(filename);
            }
        }));

        $('#viewWhereToFolderMulti').on("click", (function(e) {
            /* Open folder tree if disabled input clicked */
            e.preventDefault();
            var moveMultiModal = document.getElementById('treeMoveMultiModal');
            var instance = M.Modal.getInstance(moveMultiModal);
            instance.open();
        }));

        $('.tree-move-multi').on("click", (function(e) {
            /* Select target folder from folder tree */
            e.preventDefault();
            var target = $(this).attr('data-folder');
            var viewInput = document.getElementById('viewWhereToFolderMulti');
            var jsInput = document.getElementById('targetfoldermulti');
            var moveMultiModal = document.getElementById('treeMoveMultiModal');
            var instance = M.Modal.getInstance(moveMultiModal);
            instance.close();
            jsInput.value = target;
            viewInput.value = target;
        }));

        $('#copyMultiFileSubmit').on("click", (function(e) {
            e.preventDefault();
            const fileNames = [];
            $('input[name="selectedFile"]:checked').each(function() {
                var newInput = document.createElement("input");
                newInput.type = "hidden";
                newInput.name = "filesMove[]";
                newInput.value = this.value;
                var path = document.getElementById('current_folder_multi');
                path.appendChild(newInput);
                fileNames.push(this.value);
            });
            $.ajax({
                url: $('#targetFolderSizeForm').attr("action"),
                type: "POST",
                data: {
                    '_token': $('input[name=_token]').val(),
                    'targetfolder': $('select[name=targetfoldermulti]').val(),
                },
                success: function(data) {
                    if (typeof data.folderSize !== "undefined") {
                        $('input[name=targetFolderSize]').val(data.folderSize);
                    }
                }
            });

            document.getElementById('multiFileForm').submit();

            setInterval(function() {
                $.ajax({
                    url: $('#multiFilesCopyProgressForm').attr("action"),
                    type: "POST",
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'targetfolder': $('select[name=targetfoldermulti]').val(),
                        'targetfoldersize': $('input[name=targetFolderSize]').val(),
                        'copyfiles': fileNames,
                        'currentfolder': $('input[name=current_folder_multi]').val(),
                    },
                    success: function(data) {
                        if (typeof data.progress !== "undefined") {
                            var progressBar = document.getElementById('multiFilesCopyProgress');
                            progressBar.style.width = data.progress + "%";
                        }
                    }
                });
            }, 1000);

        }));

        /*  Remove files mechanics */
        $('.remove-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=filename]').val(filename);
            $('.filetoremove').html(filename);

        }));
        $('.remove-files-multi').on("click", (function(e) {
            e.preventDefault();
            if ($('input[name="selectedFile"]:checked').length == 0) {
                M.toast({
                    html: 'Nothing to do! No files selected'
                });
            } else {
                const fileNames = [];
                $('input[name="selectedFile"]:checked').each(function() {
                    var newInput = document.createElement("input");
                    newInput.type = "hidden";
                    newInput.name = "filesDelete[]";
                    newInput.value = this.value;
                    var path = document.getElementById('currentFolderDeleteMulti');
                    path.appendChild(newInput);
                    fileNames.push(this.value + '; ');
                });
                $('.showfilestoremove').html(fileNames);
            }
        }));

        $('#zipNDownloadFiles').on("click", (function(e) {
            /* Multifile download mechanics */
            if ($('input[name="selectedFile"]:checked').length >= 1) {
                $('input[name="selectedFile"]:checked').each(function() {
                    var newInput = document.createElement("input");
                    newInput.type = "hidden";
                    newInput.name = "filesdownload[]";
                    newInput.value = this.value;
                    var path = document.getElementById('currentFolderMultiDownload');
                    path.appendChild(newInput);
                });
                document.getElementById('multiZipFileName').value = 'zpd_multipleFiles' + Date.now() + '.zip';
                document.getElementById('multifiledownloadform').submit();

                /*  UI for preparing download */
                var elem = document.getElementById('modalbgworking');
                var instance = M.Modal.getInstance(elem);
                var forWhat = document.getElementById('preparing');
                forWhat.innerHTML = "Preparing Zip and Download !!! Be patient.";
                instance.open();
                var checkFile = setInterval(function() {
                    $.ajax({
                        url: $('#fileReadinessForm').attr("action"),
                        type: "POST",
                        data: {
                            '_token': $('input[name=_token]').val(),
                            'filePath': '/ZTemp/' + document.getElementById('multiZipFileName').value,
                        },
                        success: function(data) {
                            if (typeof data.ready !== "undefined") {
                                if (data.ready === true) {
                                    $('input[name="selectedFile"]:checked').prop("checked", false);
                                    instance.close();
                                    clearInterval(checkFile);
                                }
                            }
                        }
                    });
                }, 2000);
            } else {
                M.toast({
                    html: 'Nothing to do! No files selected'
                });
            }

        }));
        /*  fixed bottom menu bar */

        if (document.documentElement.scrollHeight >= window.innerHeight) {
            if ($("#selectedaction").hasClass("selectedaction")) {
                $("#selectedaction").removeClass("selectedaction");
            }
        }
        window.onscroll = function() {
            if (window.innerHeight + window.pageYOffset >= document.body.offsetHeight - 100) {
                if ($("#selectedaction").hasClass("selectedaction")) {
                    $("#selectedaction").removeClass("selectedaction");
                }
            } else {
                if (!$("#selectedaction").hasClass("selectedaction")) {
                    $("#selectedaction").addClass("selectedaction blue-grey lighten-4");
                }
            }
        }
    });
</script>