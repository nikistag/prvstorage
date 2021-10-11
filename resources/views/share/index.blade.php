@extends('layouts.app', ['title' => 'Shares'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span>% of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
</div>
<a href="{{route('folder.root', ['current_folder' => '/'])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h4>Shares</h4>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Row</th>
            <th>Share path</th>
            <th>Expiration</th>
            <th>Status</th>
            <th>Remove</th>
        </tr>
    </thead>
    <tbody>
        @if(count($shares) == 0)
        <tr>
            <td colspan="5">There are no shares defined.!!!</td>
        </tr>
        @else
        @foreach($shares as $share)
        <tr>
            <td>{{$loop->iteration}}</td>
            <td>{{$share->path}}</td>
            <td>{{$share->expirationDate}}</td>
            <td>{{$share->status}}</td>
            <td>
                <a href="{{$share->path}}" class="modal-trigger remove-file tooltipped" data-target="modalremovefile" data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>

<!-- Remove file modal -->
<div id="modalremovefile" class="modal">
    <form method="POST" action="{{ route('share.delete') }}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete this file?</h5>
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
            <button class="btn-small waves-effect waves-light" type="submit" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>
<script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
        $('.modal').modal();

        $('.remove-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=filename]').val(filename);
            $('.filetoremove').html(filename);

        }));

    });
</script>


@endsection