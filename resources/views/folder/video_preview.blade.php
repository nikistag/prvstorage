<p id="mediaFileName">
    <label>
        &nbsp;&nbsp;
        @if($checked == 'true')
        <input name="selectedPreviewFile" id="selectOnPreview" value="{{$fullfilename}}" type="checkbox"
            checked="checked" />
        @else
        <input name="selectedPreviewFile" id="selectOnPreview" value="{{$fullfilename}}" type="checkbox" />
        @endif
        <span>{{$fullfilename}}</span>
    </label>
</p>
@if($success)
<video style="{{$previewStyle}}" autoplay muted loop class="hide-on-small-only">
    <source src="{{asset($filevideourl)}}" type="video/mp4">
    Your browser does not support the video tag.
</video>
<video style="{{$previewStyle}}" autoplay muted loop class="hide-on-med-and-up">
    <source src="{{asset($filevideourl)}}" type="video/mp4">
    Your browser does not support the video tag.
</video>

@else
<h5 class="text-red">Preview could not be loaded for this video.</h5>
@endif