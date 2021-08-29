@extends('layouts.app', ['title' => 'Share link'])

@section('content')

<a href="{{route('folder.root')}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h5>Link generated successfuly for file/folder <span class="orange-text">{{$share_name}}</span></h5>
<p>Copy this link and send it by mail/SMS/other means</p>
<!--  Text area with copy option -->

<div class="row">
    <form class="col s12">
        <div class="row">
            <div class="input-field col s12">
                <textarea id="sharelink" class="materialize-textarea">
                {{route('share.download', ['code' => $share->code])}}
                </textarea>
                <label for="textarea1">Share link</label>
            </div>
            <button class="btn waves-effect waves-light" name="copy" id="clipboardCopy">Copy
                <i class="material-icons right">content_copy</i>
            </button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('#clipboardCopy').on('click', (function(e) {
            e.preventDefault();
            var sharelink = document.getElementById('sharelink');
            /* Select the text field */
            sharelink.select();
            sharelink.setSelectionRange(0, 99999); /* For mobile devices */
            /* Copy the text inside the text field */
            document.execCommand('copy')
            /* Alert the copied text */
            M.toast({
                html: "Copied the text: " + sharelink.value
            });
           
        }));
    });
</script>
@endsection