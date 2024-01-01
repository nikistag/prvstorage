<p id="mediaFileName">{{$fullfilename}}</p>
@if($success)
<div style="position: relative;">
    <video style="max-height:450px;max-width:600px;" autoplay muted loop class="hide-on-small-only">
        <source src="{{asset($filevideourl)}}" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <video style="max-width:220px;max-height:300px;" autoplay muted loop class="hide-on-med-and-up">
        <source src="{{asset($filevideourl)}}" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="preview-checkbox">
        <label>
            &nbsp;&nbsp;
            @if($checked == 'true')
            <input name="selectedPreviewFile" id="selectOnPreview" value="{{$fullfilename}}" type="checkbox"
                checked="checked" />
            @else
            <input name="selectedPreviewFile" id="selectOnPreview" value="{{$fullfilename}}" type="checkbox" />
            @endif
            <span>Select&nbsp;&nbsp;</span>
        </label>
    </div>
</div>

@else
<h5 class="text-red">Preview could not be loaded for this video.</h5>
@endif