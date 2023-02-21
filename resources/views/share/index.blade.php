@extends('layouts.app', ['title' => 'Shares'])

@section('content')

<div class="row">
    <div><span><?= $quota ?></span>% of disk space in use. <?= $disk_free_space ?> Gb free space</div>
    <div class="progress">
        <div class="determinate" style="width: {{$quota}}%"></div>
    </div>
</div>
<a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h4>Shares</h4>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Row</th>
            <th>Type</th>
            <th>Composition</th>
            <th>Storage</th>
            <th>Expiration</th>
            <th>Downloads</th>
            <th>Status</th>
            <th>Copy link</th>
            <th>Edit</th>
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
            <td>{{$share->type}}</td>
            <td>{{$share->composition}}</td>
            <td>{{$share->readableStorage['size']}} {{$share->readableStorage['type']}}</td>
            <td>{{$share->expirationDate}}</td>
            <td>{{$share->downloadType}}</td>
            <td>{{$share->status}}</td>
            <td>
                <input type="hidden" id="link_{{$share->id}}" value="{{$share->link}}" />
                <a href="" data-custom="{{$share->id}}" class="tooltipped clipboard-copy" data-tooltip="Copy hyperlink"><i class="material-icons blue-text">content_copy</i></a>
            </td>
            <td>
                <input type="hidden" id="composition_{{$share->id}}" value="{{$share->composition}}" />
                <input type="hidden" id="expiration_{{$share->id}}" value="{{$share->expirationDate}}" />
                <input type="hidden" id="unlimited_{{$share->id}}" value="{{$share->unlimited}}" />
                <input type="hidden" id="route_{{$share->id}}" value="{{route('share.update', ['share' => $share->id])}}" />
                <a href="" id="{{$share->id}}" class="edit-share tooltipped" data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
            </td>
            <td>
                <a href="{{$share->path}}" class="modal-trigger remove-file tooltipped" data-target="modalremovefile" data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
<!-- Edit share modal -->
<div id="pickerContainer"></div>
<div id="modalfileshare" class="modal">
    <form id="fileshareform" method="POST" action="">
        <div class="modal-content">
            <h5>Share file with "the wild"</h5>
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col s12">
                    <i>File to share:</i>
                    <strong><i><span id="showFileToShare"></span></i></strong>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <div class="switch">
                            <label>
                                With unlimited downloads
                                <input type="checkbox" name="unlimited" id="unlimited">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s12">
                    <input id="expiration" name="expiration" type="text" class="datepicker">
                    <label for="first_name">Available till:</label>
                </div>
            </div>
            <div class="modal-footer" id="multifilefooter">
                <button id="submit-share-file" class="btn-small waves-effect waves-light" type="submit" name="action">Modify
                    <i class="material-icons right">share</i>
                </button>
                <a href="#!" id="close-share-file-modal" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
            </div>
        </div>
    </form>
</div>
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
        $('.modal').modal();
        $('#modalfileshare').modal({
            dismissible: false,
        });
        $('.datepicker').datepicker({
            container: $('#pickerContainer'),
        });
        /* Open modal to edit share with the wild */
        $('.edit-share').on("click", (function(e) {
            e.preventDefault();
            //Getting data for share
            var shareId = $(this).attr('id');
            var composition = document.getElementById('composition_' + shareId);
            var expiration = document.getElementById('expiration_' + shareId);
            var unlimited = document.getElementById('unlimited_' + shareId);
            var route = document.getElementById('route_' + shareId);
            //Update form elements
            var updateForm = document.getElementById('fileshareform');
            var showFileForm = document.getElementById('showFileToShare');
            var unlimitedForm = document.getElementById('unlimited');
            var expirationForm = document.getElementById('expiration');
            //Set default valuaes for modal/form
            updateForm.action = route.value;
            showFileForm.appendChild(document.createTextNode(composition.value));
            if (unlimited.value == 1) {
                unlimitedForm.checked = true;
            } else {
                unlimitedForm.checked = false;
            }
            expirationForm.value = expiration.value;
            M.updateTextFields();
            //Open modal            
            var editmodal = document.getElementById('modalfileshare');
            var instance = M.Modal.getInstance(editmodal);
            instance.open();

        }));
        //Reset update form
        $('#close-share-file-modal').on("click", (function(e) {
            var updateForm = document.getElementById('fileshareform');
            var showFileForm = document.getElementById('showFileToShare');
            var unlimitedForm = document.getElementById('unlimited');
            var expirationForm = document.getElementById('expiration');
            updateForm.action = "";
            if (showFileForm.hasChildNodes()) {
                showFileForm.removeChild(showFileForm.firstChild);
            }
            unlimitedForm.checked = false;
            expirationForm.value = "";

        }));
        //Remove 
        $('.remove-file').on("click", (function(e) {
            e.preventDefault();
            var filename = $(this).attr('href');
            $('input[name=filename]').val(filename);
            $('.filetoremove').html(filename);
        }));

        $('.clipboard-copy').on('click', (function(e) {
            e.preventDefault();
            var shareId = $(this).attr('data-custom');
            var sharelink = document.getElementById('link_' + shareId);
            var linkArea = document.createElement("textarea");
            linkArea.style.position = 'fixed';
            linkArea.style.top = 0;
            linkArea.style.left = 0;
            linkArea.style.width = '2em';
            linkArea.style.height = '2em';
            linkArea.style.padding = 0;
            linkArea.style.border = 'none';
            linkArea.style.outline = 'none';
            linkArea.style.boxShadow = 'none';
            linkArea.style.background = 'transparent';
            linkArea.value = sharelink.value;

            document.body.appendChild(linkArea);
            linkArea.select();
            linkArea.setSelectionRange(0, 99999);
            document.execCommand('copy');
            document.body.removeChild(linkArea);
            M.toast({
                html: "Copied download link: " + sharelink.value
            });
        }));

    });
</script>


@endsection