<p id="mediaFileName">{{$fullfilename}}</p>
@if($success)
<video style="max-height:450px;max-width:600px;" autoplay muted loop class="hide-on-small-only">
    <source src="{{asset($filevideourl)}}" type="video/mp4">
    Your browser does not support the video tag.
</video>
<video style="max-width:220px;max-height:300px;" autoplay muted loop class="hide-on-med-and-up">
    <source src="{{asset($filevideourl)}}" type="video/mp4">
    Your browser does not support the video tag.
</video>
@else
<h5 class="text-red">Preview could not be loaded for this video.</h5>
@endif