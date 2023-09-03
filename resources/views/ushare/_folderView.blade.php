@if(count($directories) == 0)

@else
@foreach($directories as $directory)
<!-- New version -->
@if($directory['foldersize']["type"] == "link")
<div class="row tooltipped" data-tooltip="Path to shared folder" data-position="left">
    @else
    <div class="row tooltipped" data-tooltip="{{count(Storage::disk('local')->allDirectories($path['path'].'/'.$directory['foldername']))}} Dirs/ {{count(Storage::disk('local')->allFiles($path['path'].'/'.$directory['foldername']))}} files" data-position="left">
        @endif
        <div class="col s4 left-align" style="position: relative;">
            <a href="{{route('ushare.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}" class="valign-wrapper">
                <i class="material-icons large orange-text">folder</i>
            </a>
        </div>
        <div class="col s4">
            <span class="new badge" data-badge-caption="{{ $directory['foldersize']['type']}}">{{ $directory['foldersize']['size']}}</span>
        </div>
        <div class="col s4 right-align">
            @if($directory['foldersize']['type'] !== "link")
            <a href="{{$directory['foldername']}}" class="modal-trigger move-folder tooltipped" data-target="modalmove" data-tooltip="Copy"><i class="material-icons purple-text">content_copy</i></a>
            <br />
            <a href="{{route('ushare.folderdownload', ['path' => $current_folder == null ? '/'.$directory['foldername'] : $current_folder.'/'.$directory['foldername'], 'directory' => $directory['foldername']])}}" id="zipNdownload" class="tooltipped zipNdownload" data-tooltip="Zip & Download"><i class="material-icons blue-text">cloud_download</i></a>
            @endif
        </div>
    </div>
    <div class="row" style="border-bottom: 1px solid gray;">
        <div class="col s12 left-align">
            <a href="{{route('ushare.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}" class="valign-wrapper">
                <span class="hide-on-small-only">{{$directory['foldername']}}</span>
                <span class="hide-on-med-and-up tooltipped" data-tooltip="{{$directory['foldersize']['size']}}">{{$directory['shortfoldername']}}</span><br>
            </a>
        </div>
    </div>
    @endforeach
    @endif

    <!-- MODALS FOR FOLDER MANIPULATION -->

    <!-- Directory tree move folder modal -->
    <div id="treeMoveFolderModal" class="modal">
        <div class="modal-footer">
            <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
        </div>
        <div class="modal-content">
            <h5>Folder tree</h5>
            <p>Click / tap <b><i>folder icon</i> <u>to expand</u></b><br /> Click / tap <b><i>folder name</i> <u>to select</u></b></p>
            <ul id="treeViewFolder" class="browser-default left-align">
                {!!$treeMoveFolder!!}
            </ul>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close tooltipped btn-small red" data-tooltip="Close"><i class="material-icons white-text">close</i></a>
        </div>
    </div>

    <!-- Move/Copy folder modal -->
    <div id="modalmove" class="modal">
        <form id="moveFolderForm" method="POST" action="{{ route('ushare.folderMove') }}">
            <div class="modal-content">
                <h5>Copy folder</h5>
                @csrf
                <div class="row">
                    <div class="col s12 centered">
                        Copy folder:
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

   

    <!-- SCRIPTS FOR FOLDER MANIPULATION -->
    <script>
        $(document).ready(function() {
            $('.datepicker').datepicker({
                container: $('#pickerContainer'),
            });
           
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