@if(count($directories) == 0)

@else
@foreach($directories as $directory)
<!-- New version -->
<div class="row tooltipped" data-tooltip="{{count(Storage::disk('local')->allDirectories($path.'/'.$directory['foldername']))}} Dirs/ {{count(Storage::disk('local')->allFiles($path.'/'.$directory['foldername']))}} files" data-position="left">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}" class="valign-wrapper">
            <i class="material-icons large orange-text">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{ $directory['foldersize']['type']}}">{{ $directory['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
        <a href="{{$directory['foldername']}}" class="modal-trigger edit-folder tooltipped" data-target="modaledit" data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
        <a href="{{$directory['foldername']}}" class="modal-trigger share-folder tooltipped" data-target="modalfoldershare" data-tooltip="Share outside app"><i class="material-icons blue-text">share</i></a>
        <br />
        <a href="{{$directory['foldername']}}" class="modal-trigger move-folder tooltipped" data-target="modalmove" data-tooltip="Move/Copy"><i class="material-icons orange-text">content_copy</i></a>
        <a href="{{route('folder.folderdownload', ['path' => $current_folder == null ? '/'.$directory['foldername'] : $current_folder.'/'.$directory['foldername'], 'directory' => $directory['foldername']])}}" id="zipNdownload" class="tooltipped zipNdownload" data-tooltip="Zip & Download"><i class="material-icons blue-text">cloud_download</i></a>
        <br />
        <a href="{{$directory['foldername']}}" class="modal-trigger remove-folder tooltipped" data-target="modalremove" data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
        <!-- Hidden form for sharing files and folders -->
        <form method="POST" id="shareform{{$directory['foldername']}}" action="{{route('share.folder')}}">
            @csrf
            <input type="hidden" name="share-folder" value="{{$path . '/' . $directory['foldername']}}">
        </form>
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}" class="valign-wrapper">
            <span class="hide-on-small-only">{{$directory['foldername']}}</span>
            <span class="hide-on-med-and-up tooltipped" data-tooltip="{{$directory['foldersize']['size']}}">{{$directory['shortfoldername']}}</span><br>
        </a>
    </div>
</div>
@endforeach
@endif

<!-- 'NShare' folder actions -->
@if($current_folder == "")
<div class="row tooltipped" data-tooltip="{{count(Storage::disk('local')->allDirectories('NShare'))}} Dirs/ {{count(Storage::disk('local')->allFiles('NShare'))}} files" data-position="left">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => '/NShare'])}}" class="valign-wrapper">
            <i class="material-icons indigo-text large">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{$NShare['foldersize']['type']}}">{{$NShare['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/NShare'])}}" class="valign-wrapper">NShare</a>
    </div>
</div>

<!-- 'ZTemp' folder actions -->
<div class="row tooltipped" data-tooltip="{{count(Storage::disk('local')->allDirectories($path.'/ZTemp'))}} Dirs/ {{count(Storage::disk('local')->allFiles($path.'/ZTemp'))}} files">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => '/ZTemp'])}}" class="valign-wrapper">
            <i class="material-icons lime-text large">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{ $ztemp['foldersize']['type']}}">{{ $ztemp['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
    </div>

    <div class="col s4 right-align">
        <a href="{{'app/prv/' . auth()->user()->name.'/ZTemp'}}" class="modal-trigger empty-temp tooltipped" data-target="modalemptytemp" data-tooltip="Empty temporary folder"><i class="material-icons red-text">delete_sweep</i></a>
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
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
    <div class="modal-content">
        <h5>Folder tree</h5>
        {!!$treeMoveFolder!!}
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
    </div>
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
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
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
    <form id="moveFolderForm" method="POST" action="{{ route('folder.moveFolder') }}">
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
                <div class="col s12 centered">
                    Move folder:
                    <div class="input-field inline">
                        <input id="movefolder" name="movefolder" type="text" class="valid" value="" size="30" disabled />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12 centered">
                    To folder:
                    <div class="input-field inline">
                        <input id="viewtarget" type="text" class="valid treeMoveFolderModalTrigger" value="" size="30" readonly />
                    </div>
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

<!-- Upload folder modal -->
<div id="modalfolderupload" class="modal">
    <form id="folderuploadform" method="POST" action="{{ route('folder.folderupload') }}" enctype="multipart/form-data">
        <div class="modal-content">
            <h4>Pick folder to upload</h4>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="file-field input-field">
                        <div class="btn">
                            <span>Folder</span>
                            <input id="folderupload" name="folderupload[]" type="file" class="valid" webkitdirectory mozdirectory msdirectory odirectory directory multiple="multiple" />
                        </div>
                        <div class="file-path-wrapper">
                            <input class="file-path validate" type="text" placeholder="Upload folder">
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
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
    <!-- Upload and saving progress -->
    <div class="collection" id='folder-list-display'>

    </div>

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
                <button id="submit-share-folder" class="btn-small waves-effect waves-light" type="submit" name="action">Share
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-share-folder-modal" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>
<!-- SCRIPTS FOR FOLDER MANIPULATION -->
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            container: $('#pickerContainer'),
        });
        /* SHARE FOLDER MECHANICS */
        /* Clear share folder modal form */
        $('#close-share-folder-modal').on("click", (function(e) {
            $('#showFolderToShare').html("");
            $('#folderToShareInput').val("");
            $('#expiration_folder').val("");
            $('#unlimited_folder').prop('checked', false);
        }));
        /* Open modal to share folder with the wild */
        $('.share-folder').on("click", (function(e) {
            e.preventDefault();
            var folderToShare = $(this).attr('href');
            $('#showFolderToShare').html(folderToShare);
            $('#folderToShareInput').val('{{$path}}' + '/' + folderToShare);
        }));
        /** Submit folder share with the wild form */
        $('#submit-share-folder').on("click", (function(e) {
            e.preventDefault();
            var elem = document.getElementById('modalbgworking');
            var instance = M.Modal.getInstance(elem);
            instance.open();
            var forWhat = document.getElementById('preparing');
            forWhat.innerHTML = "Preparing share";
            document.getElementById('foldershareform').submit();
        }));
        /* END OF SHARE FOLDER MECHANICS */
        /* Manage link to share folder */
       /*  $('.sharelink-folder').on("click", (function(e) {
            e.preventDefault();
            var elem = document.getElementById('modalbgworking');
            var instance = M.Modal.getInstance(elem);
            instance.open();
            var forWhat = document.getElementById('preparing');
            forWhat.innerHTML = "Preparing share";
            var fileshareinput = document.getElementById('fileshareinput');
            folderShareForm = 'shareform' + $(this).attr('href');
            document.getElementById(folderShareForm).submit();
        }));
 */
        $('.edit-folder').on("click", (function(e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=editfolder]').val(foldername);
            $('input[name=oldfolder]').val(foldername);

        }));

        $('.remove-folder').on("click", (function(e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=folder]').val(foldername);
            $('.foldertoremove').html(foldername);

        }));

        /* Folder move / copy mechanics */
        $('.move-folder').on("click", (function(e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=movefolder]').val(foldername);
            $('input[name=whichfolder]').val(foldername);
            var viewInput = document.getElementById('viewtarget');
            var jsInput = document.getElementById('target');
            jsInput.value = "";
            viewInput.value = "";
        }));
        $('.tree-move-folder').on("click", (function(e) {
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
        }));
        $('#viewtarget').on("click", (function(e) {
            /* Open folder tree if disabled input clicked */
            e.preventDefault();
            var moveFolderModal = document.getElementById('treeMoveFolderModal');
            var instance = M.Modal.getInstance(moveFolderModal);
            instance.open();
        }));
        $('#moveFolderSubmit').on("click", (function(e) {
            e.preventDefault();
            $('#moveFolderForm').submit();
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

        }));

        $('.zipNdownload').on("click", (function(e) {
            var elem = document.getElementById('modalbgworking');
            var instance = M.Modal.getInstance(elem);
            var forWhat = document.getElementById('preparing');
            forWhat.innerHTML = "Preparing Zip and Download folder";
            instance.open();
            setInterval(function() {
                instance.close();
            }, 3000);

        }));
    });
</script>