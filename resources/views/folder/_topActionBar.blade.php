<div class="row">
    @if(($current_folder != "/ZTemp") && (substr($current_folder, 0, 7) != "/NShare"))
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