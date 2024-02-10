@if(count($directories) == 0)

@else
@foreach($directories as $directory)
<!-- New version -->
<div class="row tooltipped"
    data-tooltip="{{count(Storage::disk('local')->allDirectories($path.'/'.$directory['foldername']))}} Dirs/ {{count(Storage::disk('local')->allFiles($path.'/'.$directory['foldername']))}} files"
    data-position="left">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}"
            class="valign-wrapper">
            <i class="material-icons large orange-text">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{ $directory['foldersize']['type']}}">{{
            $directory['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
        <a href="{{$directory['foldername']}}" class="modal-trigger edit-folder tooltipped" data-target="modaledit"
            data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
        <a href="{{$directory['foldername']}}" class="modal-trigger move-folder tooltipped" data-target="modalmove"
            data-tooltip="Move/Copy"><i class="material-icons purple-text">content_copy</i></a>
        <br />
        <a href="{{$directory['foldername']}}" class="modal-trigger share-folder tooltipped"
            data-target="modalfoldershare" data-tooltip="Share outside app"><i
                class="material-icons blue-text">share</i></a>
        <a href="{{$directory['foldername']}}" class="modal-trigger user-share-folder tooltipped"
            data-target="modalusershare" data-tooltip="Share with user"><i
                class="material-icons purple-text">share</i></a>
        <br />
        <a href="{{route('folder.folderdownload', ['path' => $current_folder == null ? '/'.$directory['foldername'] : $current_folder.'/'.$directory['foldername'], 'directory' => $directory['foldername']])}}"
            id="zipNdownload" class="tooltipped zipNdownload" data-tooltip="Zip & Download"><i
                class="material-icons blue-text">cloud_download</i></a>
        <a href="{{$directory['foldername']}}" class="modal-trigger remove-folder tooltipped" data-target="modalremove"
            data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}"
            class="valign-wrapper">
            <span class="hide-on-small-only">{{$directory['foldername']}}</span>
            <span class="hide-on-med-and-up tooltipped"
                data-tooltip="{{$directory['foldersize']['size']}}">{{$directory['shortfoldername']}}</span><br>
        </a>
    </div>
</div>
@endforeach
@endif

<!-- 'NShare' folder actions -->
@if($current_folder == "")
<div class="row tooltipped"
    data-tooltip="{{count(Storage::disk('local')->allDirectories('NShare'))}} Dirs/ {{count(Storage::disk('local')->allFiles('NShare'))}} files"
    data-position="left">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => '/NShare'])}}" class="valign-wrapper">
            <i class="material-icons indigo-text large">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge"
            data-badge-caption="{{$NShare['foldersize']['type']}}">{{$NShare['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/NShare'])}}"
            class="valign-wrapper">NShare</a>
    </div>
</div>
@if(isset($usershares))
<!-- 'UShare' folder actions -->
<div class="row tooltipped" data-tooltip="Folders shared by other users" data-position="left">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('ushare.start')}}" class="valign-wrapper">
            <i class="material-icons purple-text large">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{$usershares}}"></span>
    </div>
    <div class="col s4 right-align">
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('ushare.start')}}" class="valign-wrapper">UShare</a>
    </div>
</div>
@endif

<!-- 'ZTemp' folder actions -->
<div class="row tooltipped"
    data-tooltip="{{count(Storage::disk('local')->allDirectories($path.'/ZTemp'))}} Dirs/ {{count(Storage::disk('local')->allFiles($path.'/ZTemp'))}} files">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => '/ZTemp'])}}" class="valign-wrapper">
            <i class="material-icons lime-text large">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{ $ztemp['foldersize']['type']}}">{{
            $ztemp['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
    </div>

    <div class="col s4 right-align">
        <a href="{{'app/prv/' . auth()->user()->name.'/ZTemp'}}" class="modal-trigger empty-temp tooltipped"
            data-target="modalemptytemp" data-tooltip="Empty temporary folder"><i
                class="material-icons red-text">delete_sweep</i></a>
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('folder.root', ['current_folder' => '/ZTemp'])}}" class="valign-wrapper">ZTemp</a>
    </div>
</div>
@endif

<!-- MODALS FOR FOLDER MANIPULATION -->

<!-- Directory tree move folder modal -->
<div id="treeMoveFolderModal" class="modal">
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i
                class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        <p>Click / tap <b><i>folder icon</i> <u>to expand</u></b><br /> Click / tap <b><i>folder name</i> <u>to
                    select</u></b></p>
        <ul id="treeViewFolder" class="browser-default left-align">
            {!!$treeMoveFolder!!}
        </ul>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i
                class="material-icons white-text">close</i></a>
    </div>
</div>

<!-- New folder modal -->
<div id="modal1" class="modal">
    <form method="POST" action="{{ route('folder.folderNew') }}">
        <div class="modal-content">
            <h5>New folder</h5>
            @csrf
            <div class="row">
                <div class="input-field col s12">
                    <input id="newfolder" name="newfolder" type="text" class="valid" value="" size="40" />
                    <label for="newfolder">New folder</label>
                </div>
            </div>
            <div class="row">
                <input type="hidden" name="current_folder" value="{{$current_folder}}" />
                <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                    <i class="material-icons right">send</i>
                </button>
                <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>

<!-- Edit folder modal -->
<div id="modaledit" class="modal">
    <form method="POST" action="{{ route('folder.folderEdit') }}">
        <div class="modal-content">
            <h5>Edit folder</h5>
            @csrf
            <div class="row">
                <div class="input-field col s12">
                    <label for="editfolder">Folder name</label>
                    <input id="editfolder" name="editfolder" type="text" class="valid" value="" size="40" />
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldfolder" name="oldfolder" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Move folder modal -->
<div id="modalmove" class="modal">
    <form id="moveFolderForm" method="POST" action="{{ route('folder.folderMove') }}">
        <div class="modal-content">
            <h5>Move/copy folder</h5>
            @csrf
            <div class="row">
                <div class="col s12 centered">
                    <p>
                        <label>
                            <input type="checkbox" class="filled-in" name="foldercopy" />
                            <span>Copy</span>
                        </label>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="movefolder" name="movefolder" type="text" class="valid" value="" size="30"
                        style="color:black; font-weight: bold;" disabled />
                    <label for="movefolder">Selected folder</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="viewtarget" type="text" class="valid treeMoveFolderModalTrigger" value="" size="30"
                        style="color:black; font-weight: bold;" readonly />
                    <label for="viewtarget">Destination folder</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="whichfolder" name="whichfolder" value="" />
            <input type="hidden" id="target" name="target" value="" />
            <button id="moveFolderSubmit" class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>

    <div class="progress">
        <div class="determinate" style="width: 0%" id="copyFolderProgress"></div>
    </div>

    <!-- Links used with jQuery to calculate progress bar for copy file/folders -->
    <form action="{{route('folder.folderCopyProgress')}}" id="folderCopyProgressForm"></form>

</div>

<!-- Remove folder modal -->
<div id="modalremove" class="modal">
    <form method="POST" action="{{ route('folder.folderRemove') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete this folder?</h5>
            @method('DELETE')
            @csrf
            <div class="row">
                <div class="input-field col s12">
                    <b><span class="foldertoremove"></span></b>
                    <input id="folder" name="folder" type="hidden" class="valid" value="" size="40" />
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

<!-- Upload folder modal -->
<div id="modalfolderupload" class="modal">
    <div id="folderdroparea">
        <form id="folderuploadform" method="POST" action="{{ route('folder.folderupload') }}"
            enctype="multipart/form-data">
            <div class="modal-content">
                <div class="row">
                    <div class="col s12 left-align">
                        <span class="green-text hide" style="font-size: x-small;">Drag & drop here</span>
                    </div>
                </div>
                <h4>Pick folder to upload</h4>
                @csrf
                <div class="row">
                    <div class="col s12">
                        <div class="file-field input-field">
                            <div class="btn">
                                <span>Folder</span>
                                <input id="folderupload" name="folderupload[]" type="file" class="valid" webkitdirectory
                                    mozdirectory msdirectory odirectory directory multiple="multiple" />
                                <label for="folderupload" class="hide">Folder</label>
                            </div>
                            <div class="file-path-wrapper">
                                <input id="shownfolderupload" class="file-path validate" type="text"
                                    placeholder="Upload folder">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="current_folder" value="{{$current_folder}}" />
                <button class="btn-small waves-effect waves-light" type="submit" name="action">Upload
                    <i class="material-icons right">cloud_upload</i>
                </button>
                <a href="#!" id="close-folder-upload-modal"
                    class="modal-close waves-effect waves-green deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Upload and saving progress -->
    <div class="collection" id='folder-list-display'></div>

</div>
<!-- Empty temporary folder modal -->
<div id="modalemptytemp" class="modal">
    <form method="POST" action="{{ route('folder.emptytemp') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to empty temporary folder?</h5>
            @csrf
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
<!-- Share folder modal -->
<div id="pickerContainer"></div>
<div id="modalfoldershare" class="modal">
    <form id="foldershareform" method="POST" action="{{ route('share.folder') }}">
        <div class="modal-content">
            <h5>Share folder with "the wild"</h5>
            @csrf
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <div class="row">
                <div class="col s12">
                    <i>Folder to share:</i>
                    <strong><i><span id="showFolderToShare"></span></i></strong>
                    <input type="hidden" name="folderToShareInput" id="folderToShareInput" value="" />
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <div class="switch">
                            <label>
                                With unlimited downloads
                                <input type="checkbox" name="unlimited_folder" id="unlimited_folder">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="expiration_folder" name="expiration_folder" type="text" class="datepicker">
                    <label for="expiration_folder">Available till:</label>
                </div>
            </div>
            <div class="modal-footer" id="folderfooter">
                <button id="submit-share-folder" class="btn-small waves-effect waves-light" type="submit"
                    name="action">Share
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-share-folder-modal"
                    class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>
<form id="shitForm" method="POST" action="{{ route('ushare.store') }}">
</form>
<!-- User share folder modal -->
<div id="modalusershare" class="modal">
    <form>
        <div class="modal-content">
            <h5>Share folder with user</h5>
            @csrf
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <div class="row">
                <div class="col s12">
                    <i>Folder to share:</i>
                    <strong><i><span id="showUserFolderToShare"></span></i></strong>
                    <input type="hidden" name="userFolderToShareInput" id="userFolderToShareInput" value="" />
                </div>
            </div>
            <div class="card-panel pink accent-2 hide" id="flashCardError">
                <strong><span id="errorMessage"></span></strong>
            </div>
            <div class="card-panel  green accent-2 hide" id="flashCardSuccess">
                <strong><span id="successMessage"></span></strong>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="userShareName" name="userShareName" type="text" />
                    <label for="userShareName">Username or user Email:</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="userShareExpiration" name="userShareExpiration" type="text" class="datepicker" />
                    <label for="userShareExpiration">Available till:</label>
                </div>
            </div>
            <div class="modal-footer">
                <button id="submit-user-share-folder" class="btn-small waves-effect waves-light" type="submit"
                    name="action">Share
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-user-share-folder-modal"
                    class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Close</a>
            </div>
        </div>
    </form>
</div>
<!-- SCRIPTS FOR FOLDER MANIPULATION -->
<script>
    $(document).ready(function () {
        $('.datepicker').datepicker({
            container: $('#pickerContainer'),
        });
        /* SHARE FOLDER MECHANICS */
        /* Clear share folder modal form */
        $('#close-share-folder-modal').on("click", (function (e) {
            $('#showFolderToShare').html("");
            $('#folderToShareInput').val("");
            $('#expiration_folder').val("");
            $('#unlimited_folder').prop('checked', false);
        }));
        /* Open modal to share folder with the wild */
        $('.share-folder').on("click", (function (e) {
            e.preventDefault();
            var folderToShare = $(this).attr('href');
            $('#showFolderToShare').html(folderToShare);
            $('#folderToShareInput').val('{{$path}}' + '/' + folderToShare);
        }));
        /** Submit folder share with the wild form */
        $('#submit-share-folder').on("click", (function (e) {
            e.preventDefault();
            var elemBgShareFolder = document.getElementById('modalbgworking');
            var instanceBgShareFolder = M.Modal.getInstance(elemBgShareFolder);
            instanceBgShareFolder.open();
            var forWhatBgShareFolder = document.getElementById('preparing');
            forWhatBgShareFolder.innerHTML = "Preparing zip for sharing. Please wait!";
            document.getElementById('foldershareform').submit();
        }));
        /* END OF SHARE FOLDER MECHANICS */

        /* USER SHARE FOLDER MECHANICS */
        /* Clear share folder modal form */
        $('#close-user-share-folder-modal').on("click", (function (e) {
            $('#showUserFolderToShare').html("");
            $('#userFolderToShareInput').val("");
            $('#userShareExpiration').val("");
            $('#userShareName').val("");
        }));
        /* Open modal to share folder with the wild */
        $('.user-share-folder').on("click", (function (e) {
            e.preventDefault();
            var folderToShare = $(this).attr('href');
            $('#showUserFolderToShare').html(folderToShare);
            $('#userFolderToShareInput').val('{{$path}}' + '/' + folderToShare);
        }));
        /** Submit folder share with the wild form */
        $('#submit-user-share-folder').on("click", (function (e) {
            e.preventDefault();
            var userFolderToShare = $('#userFolderToShareInput').val();
            var userShareExpiration = $('#userShareExpiration').val();
            var userShareName = $('#userShareName').val();
            if (userShareExpiration == "") {
                $('#errorMessage').html("Choose a date/ availability!");
                $('#flashCardError').removeClass("hide");
                setTimeout(function () {
                    $('#flashCardError').fadeOut(6000);
                });
                setTimeout(function () {
                    $('#flashCardError').fadeIn(10);
                    $('#flashCardError').addClass("hide");
                    $('#errorMessage').html("");
                }, 6000);
            } else {
                var x = document.getElementById("shitForm").action;
                $.ajax({
                    url: x,
                    type: "POST",
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'current_folder': $('input[name=current_folder]').val(),
                        'whichfolder': userFolderToShare,
                        'expiration': userShareExpiration,
                        'user': userShareName,
                    },
                    success: function (data) {
                        if (data.errorMessage === null) {
                            $('#successMessage').html(data.successMessage);
                            $('#flashCardSuccess').removeClass("hide");
                            setTimeout(function () {
                                $('#flashCardSuccess').fadeOut(5000);
                            });
                            setTimeout(function () {
                                $('#flashCardSuccess').fadeIn(10);
                                $('#flashCardSuccess').addClass("hide");
                                $('#successMessage').html("");
                            }, 5000);
                            setTimeout(function () {
                                $('#close-user-share-folder-modal').click();
                                var shareUserModal = document.getElementById('modalusershare');
                                var instance = M.Modal.getInstance(shareUserModal);
                                instance.close();
                            }, 5200);

                        } else {
                            $('#errorMessage').html(data.errorMessage);
                            $('#flashCardError').removeClass("hide");
                            setTimeout(function () {
                                $('#flashCardError').fadeOut(5000);
                            });
                            setTimeout(function () {
                                $('#flashCardError').fadeIn(10);
                                $('#flashCardError').addClass("hide");
                                $('#errorMessage').html("");
                            }, 5000);
                        }
                    }
                });
            }

        }));
        /* END OF SHARE FOLDER MECHANICS */

        $('.edit-folder').on("click", (function (e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=editfolder]').val(foldername);
            $('input[name=oldfolder]').val(foldername);
            M.updateTextFields();
        }));

        $('.remove-folder').on("click", (function (e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=folder]').val(foldername);
            $('.foldertoremove').html(foldername);
            M.updateTextFields();
        }));

        /* Folder move / copy mechanics */
        $('.move-folder').on("click", (function (e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=movefolder]').val(foldername);
            $('input[name=whichfolder]').val(foldername);
            var viewInput = document.getElementById('viewtarget');
            var jsInput = document.getElementById('target');
            jsInput.value = "";
            viewInput.value = "";
            M.updateTextFields();
        }));
        $('.tree-move-folder').on("click", (function (e) {
            /* Select target folder from folder tree */
            e.preventDefault();
            var target = $(this).attr('data-folder');
            var viewInput = document.getElementById('viewtarget');
            var jsInput = document.getElementById('target');
            var moveFolderModal = document.getElementById('treeMoveFolderModal');
            var instance = M.Modal.getInstance(moveFolderModal);
            instance.close();
            if (target == 'Root') {
                jsInput.value = "";
            } else {
                jsInput.value = target;
            }
            viewInput.value = $(this).attr('data-folder-view');
            M.updateTextFields();
        }));
        $('#viewtarget').on("click", (function (e) {
            /* Open folder tree if disabled input clicked */
            e.preventDefault();
            var moveFolderModal = document.getElementById('treeMoveFolderModal');
            var instance = M.Modal.getInstance(moveFolderModal);
            instance.open();
        }));
        $('#moveFolderSubmit').on("click", (function (e) {
            e.preventDefault();
            $('#moveFolderForm').submit();
            var elemBgMoveFolder = document.getElementById('modalbgworking');
            var instanceBgMoveFolder = M.Modal.getInstance(elemBgMoveFolder);
            instanceBgMoveFolder.open();
            var forWhatBgMoveFolder = document.getElementById('preparing');
            forWhatBgMoveFolder.innerHTML = "Folder copying in progress. Please wait!";
            /*          Piece of code to monitor copy progress - no viable - server too busy to respond in time   
                        setInterval(function() {
                            $.ajax({
                                url: $('#folderCopyProgressForm').attr("action"),
                                type: "POST",
                                data: {
                                    '_token': $('input[name=_token]').val(),
                                    'current_folder': $('input[name=current_folder]').val(),
                                    'whichfolder': $('input[name=whichfolder]').val(),
                                    'target': $('input[name=target]').val()
                                },
                                success: function(data) {
                                    if (typeof data.progress !== "undefined") {
                                        var progressBar = document.getElementById('copyFolderProgress');
                                        progressBar.style.width = data.progress + "%";
                                    }
                                }
                            });
                        }, 2000);
             */
        }));

        $('.zipNdownload').on("click", (function (e) {
            var elemBgZipDownload = document.getElementById('modalbgworking');
            var instanceBgZipDownload = M.Modal.getInstance(elemBgZipDownload);
            var forWhatBgZipDownload = document.getElementById('preparing');
            forWhatBgZipDownload.innerHTML = "Preparing Zip for folder download. Please wait!";
            instanceBgZipDownload.open();
            setInterval(function () {
                instanceBgZipDownload.close();
            }, 3000);

        }));
        /* Reset upload folder modal */
        $('#close-folder-upload-modal').on("click", (function (e) {
            $('#folder-list-display').empty();
            $('#folderupload').val("");
            $('#shownfolderupload').val("");
        }));
    });
</script>