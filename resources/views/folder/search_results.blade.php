@if(count($directories) == 0)

@else

@foreach($directories as $directory)
<div class="row hoverable tooltipped" data-tooltip="{{count(Storage::disk('local')->allDirectories($directory['folderpath']))}} Dirs/ {{count(Storage::disk('local')->allFiles($directory['folderpath']))}} files" style="border-bottom: 1px solid gray;">
    <div class="col s7 valign-wrapper">
        <a href="{{route('folder.root', ['current_folder' => '/'.$directory['personalfolderpath']])}}" class="valign-wrapper">
            <i class="material-icons orange-text" style="font-size:40px;">folder</i>
            <span class="hide-on-small-only">{{$directory['foldername']}}</span>
            <span class="hide-on-med-and-up">{{$directory['shortfoldername']}}</span><br>
        </a>
    </div>
    <div class="col s3 right-align">
        <span class="new badge" data-badge-caption="{{ $directory['foldersize']['type']}}">{{ $directory['foldersize']['size']}}</span>
    </div>
    <div class="col s2 right-align">
        <a href="{{route('folder.root', ['current_folder' => '/'.$directory['personalfolderpath']])}}" class="tooltipped" data-tooltip="Go to folder location"><i class="material-icons blue-text">arrow_forward</i></a>
    </div>
</div>
@endforeach
@endif


@if(count($files) == 0)
<p>You have no files stored in this directory.</p>
@else

@foreach($files as $file)
<div class="row hoverable" style="border-bottom: 1px solid gray;">
    <div class="col s7 valign-wrapper left-align">
        <i class="material-icons" style="font-size:40px;">description</i>
        <p style="margin:0; text-align: left;">
            <span class="hide-on-small-only">{{$file['filename']}}</span>
            <span class="hide-on-med-and-up tooltipped" data-tooltip="{{$file['filename']}}">{{$file['shortfilename'] . $file['extension']}}</span>
        </p>
    </div>
    <div class="col s3 right-align">
        <span class="new badge" data-badge-caption="{{ $file['filesize']['type']}}">{{ $file['filesize']['size']}}</span>
    </div>
    <div class="col s2 right-align">
        @if($current_folder == '/ZTemp')
        @else
        <a href="{{route('folder.root', ['current_folder' => '/'.$file['filefolder']])}}" class="tooltipped" data-tooltip="Go to file location"><i class="material-icons blue-text">arrow_forward</i></a>

        @endif
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