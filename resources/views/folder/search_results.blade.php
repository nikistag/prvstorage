@if(count($directories) == 0)

@else

@foreach($directories as $directory)
<div class="row tooltipped" data-tooltip="{{count(Storage::disk('local')->allDirectories($directory['folderpath']))}} Dirs/ {{count(Storage::disk('local')->allFiles($directory['folderpath']))}} files">
    <div class="col s4 left-align" style="position: relative;">
        <a href="{{route('folder.root', ['current_folder' => $current_folder . '/'. $directory['foldername']])}}" class="valign-wrapper">
            <i class="material-icons large orange-text">folder</i>
        </a>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{ $directory['foldersize']['type']}}">{{ $directory['foldersize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
        <a href="{{route('folder.root', ['current_folder' => '/'.$directory['personalfolderpath']])}}" class="tooltipped" data-tooltip="Go to folder location"><i class="material-icons blue-text">arrow_forward</i></a>
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


@if(count($files) == 0)
<p>You have no files stored in this directory.</p>
@else

@foreach($files as $file)
<div class="row">
    <div class="col s4 left-align" style="position: relative;">
        <i class="material-icons" style="font-size:40px;">description</i>
    </div>
    <div class="col s4">
        <span class="new badge" data-badge-caption="{{ $file['filesize']['type']}}">{{ $file['filesize']['size']}}</span>
    </div>
    <div class="col s4 right-align">
        @if($current_folder == '/ZTemp')
        @else
        <a href="{{route('folder.root', ['current_folder' => '/'.$file['filefolder']])}}" class="tooltipped" data-tooltip="Go to file location"><i class="material-icons blue-text">arrow_forward</i></a>
        @endif
    </div>
</div>
<div class="row" style="border-bottom: 1px solid gray;">
    <div class="col s12 left-align">
        <label>
            <span>
                <span class="hide-on-small-only grey-text text-darken-3">{{$file['filename']}}</span>
                <span class="hide-on-med-and-up tooltipped grey-text text-darken-3" data-tooltip="{{$file['filename']}}">{{$file['shortfilename'] . $file['extension']}}</span>
            </span>
        </label>
    </div>
</div>
@endforeach

@endif

<script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
        $('.modal').modal();
    });
</script>