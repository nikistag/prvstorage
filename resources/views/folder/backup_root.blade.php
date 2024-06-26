@extends('layouts.app', ['title' => 'Root folder'])

@section('content')

@include('partials._quota')

<div class="row">
    @if($current_folder != "/ZTemp")
    <div class="col s12 center blue-grey lighten-4">
        <a href="Multiple files">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modalfilesupload"
                data-position="bottom" data-tooltip="Upload multiple files"
                onclick="jsUpload('multiupload','filesupload','file-list-display')">
                playlist_add
            </i>
        </a>
        <a href="New Folder">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modal1"
                data-position="bottom" data-tooltip="Create new folder here">
                folder_open
            </i>
        </a>
        <a href="Folder upload" class="hide-on-small-only">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-target="modalfolderupload"
                data-position="bottom" data-tooltip="Upload folder"
                onclick="jsUpload('folderuploadform','folderupload','folder-list-display')">
                create_new_folder
            </i>
        </a>
        <a href="{{route('folder.searchForm', ['current_folder' => $current_folder])}}">
            <i class="material-icons medium purple-text tooltipped modal-trigger" data-position="bottom"
                data-tooltip="Search file/folder">
                search
            </i>
        </a>
    </div>
    @endif
</div>
<div class="row blue-grey">
    <div class="col s12 center white-text">
        <strong>Current folder:</strong>
        @foreach($breadcrumbs as $piece)
        @if ($loop->last)
        <span class="lime-text"><strong>{{$piece['folder']}}</strong></span>
        @else
        <a href="{{route('folder.root', ['current_folder' => $piece['path']])}}">
            <span class="orange-text text-lighten-4">{{$piece['folder']}}</span>
        </a>
        &nbsp;<strong>></strong>&nbsp;
        @endif
        @endforeach
    </div>
</div>

<!-- If in subfolder print back button -->
@if($current_folder != "")
<div class="row">
    <div class="col s12">
        <a href="{{route('folder.root', ['current_folder' => $parent_folder])}}" class="left">
            <i class="material-icons blue-text">arrow_back</i>
        </a>
    </div>
</div>
@endif


@if(count($directories) == 0)

@else

@foreach($directories as $directory)
<div class="row hoverable tooltipped"
    data-tooltip="{{count(Storage::disk('local')->allDirectories($path.'/'.$directory['foldername']))}} Dirs/ {{count(Storage::disk('local')->allFiles($path.'/'.$directory['foldername']))}} files"
    style="border-bottom: 1px solid gray;">
    <div class="col s8 valign-wrapper">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}"
            class="valign-wrapper">
            <i class="material-icons orange-text" style="font-size:40px;">folder</i>
            <span class="hide-on-small-only">{{$directory['foldername']}}</span>
            <span class="hide-on-med-and-up">{{$directory['shortfoldername']}}</span><br>
            <span class="new badge" data-badge-caption="{{ $directory['foldersize']['type']}}">{{
                $directory['foldersize']['size']}}</span>
        </a>
    </div>
    <div class="col s4 right-align">
        <!-- What is this for? -->
        @if($current_folder == "")
        <input name="directory" type="hidden"
            value="{{'app/prv/' . auth()->user()->name.'/'.$directory['foldername']}}" />
        @else
        <input name="directory" type="hidden"
            value="{{'app/prv/' . auth()->user()->name.'/'.$current_folder.'/'.$directory['foldername']}}" />
        @endif
        <a href="{{$directory['foldername']}}" class="modal-trigger edit-folder tooltipped" data-target="modaledit"
            data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
        <a href="{{$directory['foldername']}}" class="tooltipped sharelink-folder" data-tooltip="Share"><i
                class="material-icons blue-text">share</i></a>
        <a href="{{$directory['foldername']}}" class="modal-trigger move-folder tooltipped" data-target="modalmove"
            data-tooltip="Move/Copy"><i class="material-icons orange-text">content_copy</i></a>
        <br />
        <a href="{{route('folder.folderdownload', ['path' => $current_folder == null ? '/'.$directory['foldername'] : $current_folder.'/'.$directory['foldername'], 'directory' => $directory['foldername']])}}"
            id="zipNdownload" class="tooltipped zipNdownload" data-tooltip="Zip & Download"><i
                class="material-icons blue-text">cloud_download</i></a>
        <a href="{{$directory['foldername']}}" class="modal-trigger remove-folder tooltipped" data-target="modalremove"
            data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
        <!-- Hidden form for sharing files and folders -->
        <form method="POST" id="shareform{{$directory['foldername']}}" action="{{route('share.folder')}}">
            @csrf
            <input type="hidden" name="share-folder" value="{{$path . '/' . $directory['foldername']}}">
        </form>
    </div>
</div>
@endforeach
@endif

<!-- 'NShare' folder actions -->
@if($current_folder == "")
<div class="row hoverable tooltipped"
    data-tooltip="{{count(Storage::disk('local')->allDirectories('NShare'))}} Dirs/ {{count(Storage::disk('local')->allFiles('NShare'))}} files"
    style="border-bottom: 1px solid gray;">
    <div class="col s8 valign-wrapper">
        <a href="{{route('folder.root', ['current_folder' => '/NShare'])}}" class="valign-wrapper">
            <i class="material-icons indigo-text" style="font-size:40px;">folder</i>
            NShare
            <span class="new badge"
                data-badge-caption="{{$NShare['foldersize']['type']}}">{{$NShare['foldersize']['size']}}</span>
        </a>
    </div>
    <div class="col s4 right-align">
    </div>
</div>

<!-- 'ZTemp' folder actions -->
<div class="row hoverable tooltipped"
    data-tooltip="{{count(Storage::disk('local')->allDirectories($path.'/ZTemp'))}} Dirs/ {{count(Storage::disk('local')->allFiles($path.'/ZTemp'))}} files"
    style="border-bottom: 1px solid gray;">
    <div class="col s8 valign-wrapper">
        <a href="{{route('folder.root', ['current_folder' => '/ZTemp'])}}" class="valign-wrapper">
            <i class="material-icons lime-text" style="font-size:40px;">folder</i>
            ZTemp
            <span class="new badge" data-badge-caption="{{ $ztemp['foldersize']['type']}}">{{
                $ztemp['foldersize']['size']}}</span>
        </a>
    </div>
    <div class="col s4 right-align">
        <a href="{{'app/prv/' . auth()->user()->name.'/ZTemp'}}" class="modal-trigger empty-temp tooltipped"
            data-target="modalemptytemp" data-tooltip="Empty temporary folder"><i
                class="material-icons red-text">delete_sweep</i></a>
    </div>
</div>

@endif

@if(count($files) == 0)
<p>You have no files stored in this directory.</p>
@else

@foreach($files as $file)
<div class="row hoverable" style="border-bottom: 1px solid gray;">
    <div class="col s8 valign-wrapper left-align"
        style="background-image: url('{{asset('docs_100px.png')}}'); background-repeat: no-repeat; height: 120px;">
        <label>
            <input name="selectedFile" id="{{$file['fullfilename']}}" class="filescheck"
                value="{{$file['fullfilename']}}" type="checkbox" />
            <span></span>
        </label>
        <i class="material-icons" style="font-size:40px;">description</i>
        <p style="margin:0; text-align: left;">
            <span class="hide-on-small-only">{{$file['fullfilename']}}</span>
            <span class="hide-on-med-and-up tooltipped"
                data-tooltip="{{$file['fullfilename']}}">{{$file['shortfilename'] . $file['extension']}}</span>
            <span class="new badge" data-badge-caption="{{ $file['filesize']['type']}}">{{
                $file['filesize']['size']}}</span>
        </p>
    </div>
    <div class="col s4 right-align">
        @if($current_folder == '/ZTemp')
        @else
        <a href="{{$file['fullfilename']}}" class="modal-trigger rename-file tooltipped" data-target="modalrenamefile"
            data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
        <a href="{{$file['fullfilename']}}" class="tooltipped sharelink" data-tooltip="Share"><i
                class="material-icons blue-text">share</i></a>
        <a href="{{$file['fullfilename']}}" class="modal-trigger move-file-big tooltipped"
            data-target="modalmovefilebig" data-tooltip="Move/Copy"><i
                class="material-icons purple-text">content_copy</i></a>
        <br />
        <a href="{{route('folder.filedownload', ['path' => $current_folder == null ? '/'.$file['fullfilename'] : $current_folder.'/'.$file['fullfilename']])}}"
            class="tooltipped" data-tooltip="Download"><i class="material-icons blue-text">cloud_download</i></a>
        <a href="{{$file['fullfilename']}}" class="modal-trigger remove-file tooltipped" data-target="modalremovefile"
            data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
        @endif
    </div>
</div>

@endforeach

@if($current_folder == '/ZTemp')
@else

<div class="row center left-align blue-grey lighten-4">
    <div class="selectedaction blue-grey lighten-4" id="selectedaction">
        &nbsp;
        <a href="#share" class="tooltipped sharelink-files" data-tooltip="Share"><i
                class="material-icons medium blue-text">share</i></a>
        &nbsp;
        <a href="#copy" class="move-files tooltipped" data-tooltip="Move/Copy"><i
                class="material-icons medium purple-text">content_copy</i></a>
        &nbsp;
        <a href="#download" class="tooltipped" data-tooltip="Download" id="zipNDownloadFiles"><i
                class="material-icons medium blue-text">cloud_download</i></a>
        &nbsp;
        <a href="#delete" class="modal-trigger remove-files-multi tooltipped" data-target="modalremovefilesmulti"
            data-tooltip="Delete"><i class="material-icons medium red-text">remove_circle</i></a>
        &nbsp;
    </div>

</div>

@endif
@endif




<!-- Form for downloading multiple files -->
<form action="{{route('folder.multifiledownload')}}" id="multifiledownloadform">
    <input type="hidden" id="multiZipFileName" name="multiZipFileName" value="" />
    <input type="hidden" id="currentFolderMultiDownload" name="currentFolderMultiDownload"
        value="{{$current_folder}}" />
</form>

<!-- Form for checking file readiness -->
<form action="{{route('folder.fileReadiness')}}" id="fileReadinessForm"></form>

<!-- New folder modal -->
<div id="modal1" class="modal">
    <form method="POST" action="{{ route('folder.folderNew') }}">
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
    <form method="POST" action="{{ route('folder.folderEdit') }}">
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
    <form id="moveFolderForm" method="POST" action="{{ route('folder.folderMove') }}">
        <div class="modal-content">
            <h5>Move folder</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="movefolder" name="movefolder" type="text" class="valid" value="" size="30"
                            disabled />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <label>
                            <input type="checkbox" class="filled-in" name="foldercopy" />
                            <span>Copy</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <select id="target" name="target">
                        <option value="" disabled>Choose where</option>
                        @foreach($directory_paths as $dpath)
                        <option value="{{$dpath}}">{{$dpath}}</option>
                        @endforeach
                    </select>
                    <label>Choose folder</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="whichfolder" name="whichfolder" value="" />
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
            <input type="hidden" name="current_folder" value="{{$current_folder}}" />
            <input type="hidden" id="oldrenamefilename" name="oldrenamefilename" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>


<!-- Move file BIG modal -->
<div id="modalmovefilebig" class="modal">
    <form method="POST" action="{{ route('folder.moveFileBig') }}" id="bigFileForm">
        <div class="modal-content">
            <h5>Move/Copy file</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="fileDisplay" name="fileDisplay" type="text" class="valid" value="" size="30"
                            disabled />
                        <label for="fileDisplay"></label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <label>
                            <input type="checkbox" class="filled-in" name="filecopy" />
                            <span>Copy</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <select id="targetfolderbig" name="targetfolderbig">
                        <option value="" disabled>Choose where</option>
                        @foreach($directory_paths as $dpath)
                        <option value="{{$dpath}}">{{$dpath}}</option>
                        @endforeach
                    </select>
                    <label>Choose folder</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="current_folder_big" name="current_folder_big" value="{{$current_folder}}" />
            <input type="hidden" id="file_big" name="file_big" value="" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action" id="copyBigFileSubmit">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>

    <div class="progress">
        <div class="determinate" style="width: 0%" id="copyFileProgress"></div>
    </div>

</div>
<!-- Move multiple files modal -->
<div id="modalmovefiles" class="modal">
    <form method="POST" action="{{ route('folder.moveFileMulti') }}" id="multiFileForm">
        <div class="modal-content">
            <h5>Move/Copy file</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <input id="fileDisplayMulti" name="fileDisplayMulti" type="text" class="valid" value=""
                            size="100" disabled />
                        <label for="fileDisplayMulti"></label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <label>
                            <input type="checkbox" class="filled-in" name="filecopy" />
                            <span>Copy</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field">
                        <select id="targetfoldermulti" name="targetfoldermulti">
                            <option value="" disabled>Choose where</option>
                            @foreach($directory_paths as $dpath)
                            <option value="{{$dpath}}">{{$dpath}}</option>
                            @endforeach
                        </select>
                        <label></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" id="targetFolderSize" name="targetFolderSize" value="" />
            <input type="hidden" id="current_folder_multi" name="current_folder_multi" value="{{$current_folder}}" />
            <button class="btn-small waves-effect waves-light" type="submit" name="action"
                id="copyMultiFileSubmit">Submit
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
                        <input type="hidden" id="currentFolderDeleteMulti" name="current_folder"
                            value="{{$current_folder}}" />
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
                            <input id="folderupload" name="folderupload[]" type="file" class="valid" webkitdirectory
                                mozdirectory msdirectory odirectory directory multiple="multiple" />
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
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
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

<!-- upload files modal -->
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
            <button id="submit-files-upload" class="btn-small waves-effect waves-light" type="submit"
                name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" id="close-upload-modal"
                class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
    <div class="collection" id='file-list-display'></div>
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
    $(document).ready(function () {
        $('.tooltipped').tooltip();
        $('.modal').modal();
        $('.modalupload').modal({
            dismissible: false,
        });
        $('select').formSelect();
        /* Manage link to share file */
        $('.sharelink').on("click", (function (e) {
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
        $('.sharelink-folder').on("click", (function (e) {
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
        /* Manage link to share multiple files */
        $('.sharelink-files').on("click", (function (e) {
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
                $('input[name="selectedFile"]:checked').each(function () {
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

        $('.edit-folder').on("click", (function (e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=editfolder]').val(foldername);
            $('input[name=oldfolder]').val(foldername);

        }));

        $('.remove-folder').on("click", (function (e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=folder]').val(foldername);
            $('.foldertoremove').html(foldername);

        }));
        $('.rename-file').on("click", (function (e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=renamefilename]').val(filename);
            $('input[name=oldrenamefilename]').val(filename);

        }));
        $('.move-file-big').on("click", (function (e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=fileDisplay]').val(filename);
            $('input[name=file_big]').val(filename);
        }));

        $('#copyBigFileSubmit').on("click", (function (e) {
            e.preventDefault();
            $('#bigFileForm').submit();
            setInterval(function () {
                $.ajax({
                    url: $('#fileCopyProgressForm').attr("action"),
                    type: "POST",
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'targetfolder': $('select[name=targetfolderbig]').val(),
                        'copyfile': $('input[name=file_big]').val(),
                        'currentfolder': $('input[name=current_folder_big]').val(),
                    },
                    success: function (data) {
                        if (typeof data.progress !== "undefined") {
                            var progressBar = document.getElementById('copyFileProgress');
                            progressBar.style.width = data.progress + "%";
                        }
                    }
                });
            }, 2000);
        }));

        /* multiple files move / copy mechanics */
        $('.move-files').on("click", (function (e) {
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
                $('input[name="selectedFile"]:checked').each(function () {
                    filename = filename + this.value + ', ';
                });
                $('input[name=fileDisplayMulti]').val(filename);
            }
        }));

        $('#copyMultiFileSubmit').on("click", (function (e) {
            e.preventDefault();
            const fileNames = [];
            $('input[name="selectedFile"]:checked').each(function () {
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
                success: function (data) {
                    if (typeof data.folderSize !== "undefined") {
                        $('input[name=targetFolderSize]').val(data.folderSize);
                    }
                }
            });


            document.getElementById('multiFileForm').submit();

            setInterval(function () {
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
                    success: function (data) {
                        if (typeof data.progress !== "undefined") {
                            var progressBar = document.getElementById('multiFilesCopyProgress');
                            progressBar.style.width = data.progress + "%";
                        }
                    }
                });
            }, 1000);

        }));
        $('.move-folder').on("click", (function (e) {
            e.preventDefault();
            var foldername = $(this).attr('href');
            $('input[name=movefolder]').val(foldername);
            $('input[name=whichfolder]').val(foldername);

        }));
        $('#moveFolderSubmit').on("click", (function (e) {
            e.preventDefault();
            $('#moveFolderForm').submit();
            setInterval(function () {
                $.ajax({
                    url: $('#folderCopyProgressForm').attr("action"),
                    type: "POST",
                    data: {
                        '_token': $('input[name=_token]').val(),
                        'current_folder': $('input[name=current_folder]').val(),
                        'whichfolder': $('input[name=whichfolder]').val(),
                        'target': $('select[name=target]').val()
                    },
                    success: function (data) {
                        if (typeof data.progress !== "undefined") {
                            var progressBar = document.getElementById('copyFolderProgress');
                            progressBar.style.width = data.progress + "%";
                        }
                    }
                });
            }, 2000);

        }));
        $('.remove-file').on("click", (function (e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=filename]').val(filename);
            $('.filetoremove').html(filename);

        }));
        $('.remove-files-multi').on("click", (function (e) {
            e.preventDefault();
            if ($('input[name="selectedFile"]:checked').length == 0) {
                M.toast({
                    html: 'Nothing to do! No files selected'
                });
            } else {
                const fileNames = [];
                $('input[name="selectedFile"]:checked').each(function () {
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
        $('.zipNdownload').on("click", (function (e) {
            var elem = document.getElementById('modalbgworking');
            var instance = M.Modal.getInstance(elem);
            var forWhat = document.getElementById('preparing');
            forWhat.innerHTML = "Preparing Zip and Download folder";
            instance.open();
            setInterval(function () {
                instance.close();
            }, 3000);

        }));
        $('#zipNDownloadFiles').on("click", (function (e) {
            /* Multifile download mechanics */
            if ($('input[name="selectedFile"]:checked').length >= 1) {
                $('input[name="selectedFile"]:checked').each(function () {
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
                var checkFile = setInterval(function () {
                    $.ajax({
                        url: $('#fileReadinessForm').attr("action"),
                        type: "POST",
                        data: {
                            '_token': $('input[name=_token]').val(),
                            'filePath': '/ZTemp/' + document.getElementById('multiZipFileName').value,
                        },
                        success: function (data) {
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
        window.onscroll = function () {
            if (window.innerHeight + window.pageYOffset >= document.body.offsetHeight) {
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

    function jsUpload(form, input, display) {
        var fileCatcher = document.getElementById(form); //form
        var fileInput = document.getElementById(input); //input        
        var fileListDisplay = document.getElementById(display); //display
        var fileList = [];
        var renderFileList, sendFile;

        //personal addings
        var submitButton = document.getElementById('submit-files-upload');
        var closeButton = document.getElementById('close-upload-modal');

        fileCatcher.addEventListener('submit', function (evnt) {
            evnt.preventDefault();
            closeButton.classList.add("hide"); //my
            submitButton.classList.add("hide"); //my
            fileList.forEach(function (file) {
                sendFile(file);
            });
        });

        fileInput.addEventListener('change', function (evnt) {
            fileList = [];
            var clientFiles = Array.from(fileInput.files);
            const result = Array.isArray(clientFiles) ? clientFiles.sort(function (a, b) {
                return a.size - b.size
            }) : [];
            console.log(clientFiles);
            for (var i = 0; i < fileInput.files.length; i++) {
                fileList.push(clientFiles[i]);
            }
            renderFileList();
        });

        renderFileList = function () {
            fileListDisplay.innerHTML = '';
            fileList.forEach(function (file) { //added index
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

        sendFile = function (file) {
            var formData = new FormData();
            var request = new XMLHttpRequest();

            formData.set('file', file);
            formData.set('_token', $('input[name=_token]').val());
            formData.set('filepath', file.webkitRelativePath);
            formData.set('current_folder', $('input[name=current_folder]').val());

            request.upload.addEventListener("progress", function (evt) {
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

            request.onloadend = function () {
                if (this.status == 200) {
                    var uploadItem = document.getElementById(file.webkitRelativePath + file.name + file.size + "item");
                    uploadItem.classList.remove("notuploaded");
                    uploadItem.classList.add("hide");
                    var notdone = document.getElementsByClassName("notuploaded").length;
                    if (notdone > 0) {
                        console.log("Files still in queue");
                    } else {
                        setTimeout(function () {
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