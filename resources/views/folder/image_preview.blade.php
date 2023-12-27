<div style="position: relative;">
    <p id="mediaFileName">{{$fullfilename}}</p>

    <img class="hide-on-small-only" src="{{asset($fileimageurl)}}" style="max-height:450px;max-width:600px;"
        alt="file image" />
    <img class="hide-on-med-and-up" src="{{asset($fileimageurl)}}" style="max-width:220px;max-height:300px;"
        alt="file image" />

    <div class="preview-checkbox">
        <label>
            &nbsp;&nbsp;
            <input name="selectedPreviewFile" id="selectOnPreview" value="{{$fullfilename}}" type="checkbox" />
            <span>Select&nbsp;&nbsp;</span>
        </label>
    </div>
</div>