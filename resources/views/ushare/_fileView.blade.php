@if(count($files) == 0)
<p>You have no files stored in this directory.</p>
@else

@foreach($files as $file)

<div class="row">
    <div class="col s4 left-align" style="position: relative;">
        @if($file['filevideourl'] === null)
        <!-- Image preview -->
        <img src="{{asset($file['fileimageurl']['thumb'])}}" alt="file image" class="{{$file['fileimageurl']['original'] == true ? 'media-preview-trigger' : ''}}" data-data="{{$file['fullfilename']}}">
        @else
        <!-- Video preview -->
        <video width="100" height="100" autoplay muted loop class="media-preview-trigger" data-data="{{$file['fullfilename']}}">
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
        <a href="{{$file['fullfilename']}}" class="modal-trigger move-file-big tooltipped" data-target="modalmovefilebig" data-tooltip="Move/Copy"><i class="material-icons purple-text">content_copy</i></a>
        <br />
        <a href="{{route('ushare.filedownload', ['path' => $current_folder == null ? '/'.$file['fullfilename'] : $current_folder.'/'.$file['fullfilename']])}}" class="tooltipped" data-tooltip="Download"><i class="material-icons blue-text">cloud_download</i></a>
        <br />
        @if($file['filevideourl'] === null)
        @else
        <a href="{{route('ushare.filestream', ['path' => $current_folder == null ? '/'.$file['fullfilename'] : $current_folder.'/'.$file['fullfilename']])}}" class="tooltipped" data-tooltip="Play"><i class="material-icons blue-text">play_arrow</i></a>
        @endif
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
        <a href="#copy" class="move-files tooltipped" data-tooltip="Move/Copy"><i class="material-icons medium purple-text">content_copy</i></a>
        &nbsp;
        <a href="#download" class="tooltipped" data-tooltip="Download" id="zipNDownloadFiles"><i class="material-icons medium blue-text">cloud_download</i></a>
        &nbsp;
    </div>
</div>

@endif
@endif

<!-- Form for downloading multiple files -->
<form action="{{route('ushare.multifiledownload')}}" id="multifiledownloadform">
    <input type="hidden" id="multiZipFileName" name="multiZipFileName" value="" />
    <input type="hidden" id="currentFolderMultiDownload" name="currentFolderMultiDownload" value="{{$current_folder}}" />
</form>

<!-- Form for checking file readiness -->
<form action="{{route('ushare.fileReadiness')}}" id="fileReadinessForm"></form>

<!-- MODALS FOR FILE MANIPULATION -->

<!-- Directory tree move file modal -->
<div id="treeMoveFileModal" class="modal">
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        <p>Click / tap <b><i>folder icon</i> <u>to expand</u></b><br /> Click / tap <b><i>folder name</i> <u>to select</u></b></p>
        <ul id="treeViewFile" class="browser-default left-align">
            {!!$treeMoveFile!!}
        </ul>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
</div>

<!-- Move file BIG modal -->
<div id="modalmovefilebig" class="modal">
    <form method="POST" action="{{ route('ushare.moveFileBig') }}" id="bigFileForm">
        <div class="modal-content">
            <h5>Move/Copy file</h5>
            @csrf
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
        <p>Click / tap <b><i>folder icon</i> <u>to expand</u></b><br /> Click / tap <b><i>folder name</i> <u>to select</u></b></p>
        <ul id="treeViewMulti" class="browser-default left-align">
            {!!$treeMoveMulti!!}
        </ul>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
</div>

<!-- Move multiple files modal -->
<div id="modalmovefiles" class="modal">
    <form method="POST" action="{{ route('ushare.moveFileMulti') }}" id="multiFileForm">
        <div class="modal-content">
            <h5>Move/Copy files</h5>
            @csrf
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
    <form action="{{route('ushare.multiFilesCopyProgress')}}" id="multiFilesCopyProgressForm"></form>
    <form action="{{route('ushare.targetFolderSize')}}" id="targetFolderSizeForm"></form>

</div>

<!-- Media preview modal-->
<div id="mediaModal" class="modal">
    <div class="modal-content center-align" id="mediaPreview" style="padding:10px;">
        <h5 id="mediaFileName">Loading...</h5>
        <div class="progress">
            <div class="indeterminate"></div>
        </div>
    </div>
    <div class="modal-footer">
        <div class="row">
            <div class="col s3 right-align">
                <a href="#!" id="leftChevron" class="preview-links hide"><i class="material-icons medium">chevron_left</i></a>
            </div>
            <div class="col s6 center-align">
                <a href="#!" class="modal-close btn-small red">Close</a>
            </div>
            <div class="col s3 left-align">
                <a href="#!" id="rightChevron" class="preview-links hide"><i class="material-icons medium">chevron_right</i></a>
            </div>
        </div>
    </div>
</div>

<!-- SCRRIPTS FOR FILE MANIPULATION -->
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            container: $('#pickerContainer'),
        });
        /*MEDIA PREVIEW*/
        /** Open media preview modal*/
        $('.media-preview-trigger').on("click", (function(e) {
            e.preventDefault();
            var elem = document.getElementById('mediaModal');
            var instance = M.Modal.getInstance(elem);
            instance.open();
            /* var currentFolder = document.getElementById('current_folder'); */
            var fileName = $(this).attr('data-data');
            var previewDiv = document.getElementById('mediaPreview');
            var leftChevron = document.getElementById("leftChevron");
            var rightChevron = document.getElementById("rightChevron");
            $.ajax({
                url: "{{route('ushare.mediapreview')}}",
                type: "GET",
                data: {
                    '_token': $('input[name=_token]').val(),
                    'current_folder': $('input[name=current_folder]').val(),
                    'file_name': fileName
                },
                success: function(data) {
                    if (typeof data.html !== "undefined") {
                        previewDiv.innerHTML = data.html;
                        if (data.leftChevron == 'active') {
                            if (leftChevron.classList.contains("hide")) {
                                leftChevron.classList.remove("hide");
                            }
                            leftChevron.setAttribute("data-data", data.leftLink);
                        } else {
                            if (!leftChevron.classList.contains("hide")) {
                                leftChevron.classList.add("hide");
                            }
                        }
                        if (data.rightChevron == 'active') {
                            if (rightChevron.classList.contains("hide")) {
                                rightChevron.classList.remove("hide");
                            }
                            rightChevron.setAttribute("data-data", data.rightLink);
                        } else {
                            if (!rightChevron.classList.contains("hide")) {
                                rightChevron.classList.add("hide");
                            }
                        }
                    }
                }
            });
        }));
        $('.preview-links').on("click", (function(e) {
            e.preventDefault();
            /* var currentFolder = document.getElementById('current_folder'); */
            var fileName = $(this).attr('data-data');
            var previewDiv = document.getElementById('mediaPreview');
            var leftChevron = document.getElementById("leftChevron");
            var rightChevron = document.getElementById("rightChevron");
            previewDiv.innerHTML = '<h5 id="mediaFileName">Loading...</h5><div class="progress"><div class="indeterminate"></div></div>';
            $.ajax({
                url: "{{route('ushare.mediapreview')}}",
                type: "GET",
                data: {
                    '_token': $('input[name=_token]').val(),
                    'current_folder': $('input[name=current_folder]').val(),
                    'file_name': fileName
                },
                success: function(data) {
                    if (typeof data.html !== "undefined") {
                        previewDiv.innerHTML = data.html;
                        if (data.leftChevron == 'active') {
                            if (leftChevron.classList.contains("hide")) {
                                leftChevron.classList.remove("hide");
                            }
                            leftChevron.setAttribute("data-data", data.leftLink);
                        } else {
                            if (!leftChevron.classList.contains("hide")) {
                                leftChevron.classList.add("hide");
                            }
                        }
                        if (data.rightChevron == 'active') {
                            if (rightChevron.classList.contains("hide")) {
                                rightChevron.classList.remove("hide");
                            }
                            rightChevron.setAttribute("data-data", data.rightLink);
                        } else {
                            if (!rightChevron.classList.contains("hide")) {
                                rightChevron.classList.add("hide");
                            }
                        }
                    }
                }
            });
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
            }, 1000);
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
                var viewFolder = document.getElementById('viewWhereToFolderMulti');
                viewFolder.value = '';

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
            viewInput.value = $(this).attr('data-folder-view');
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