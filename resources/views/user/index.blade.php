@extends('layouts.app', ['title' => 'Users'])

@section('content')

<a href="{{route('folder.root', ['current_folder' => ''])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h4>Users</h4>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Row</th>
            <th>Email</th>
            <th>Name</th>
            <th>Storage used</th>
            <th>Active</th>
            <th>Admin</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @if(count($users) == 0)
        <tr>
            <td colspan="5">There are no shares defined.!!!</td>
        </tr>
        @else
        @foreach($users as $user)
        <tr>
            <td>{{$loop->iteration}}</td>
            <td>{{$user->email}}</td>
            <td>{{$user->name}}</td>
            <td>{{$user->folderSize['size']}}&nbsp;{{$user->folderSize['type']}}</td>
            <td>{{$user->active_status}}</td>
            <td>{{$user->admin_role}}</td>
            <td>
                <a href="{{ route('user.edit', ['user' => $user->id])}}" class="tooltipped" data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
                <a id="{{$user->id}}" href="{{$user->name . ', with email: ' . $user->email}}" class="modal-trigger remove-user tooltipped" data-target="modalremoveuser" data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>

<!-- Remove user modal -->
<div id="modalremoveuser" class="modal">
    <form id="deleteform" method="POST" action="{{route('user.destroy')}}">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete this user?</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <strong><i><span class="usertoremove"></span></i></strong>
                        <input id="userid" name="userid" type="hidden" class="valid" value="" />
                        <input id="userName" name="userName" type="hidden" class="valid" value="" />
                        <label for="userid"></label>
                    </div>
                </div>
                <div class="col s12">
                    <label>
                        <input id="backup" type="checkbox" class="filled-in" name="backup" value="backup" />
                        <span>Create backup for user data before deleting</span>
                    </label>
                </div>
            </div>
            <div id="preparingBackup" class="row hide">
                <div class="col s12">
                    <h5 class="red-text" id="preparing"></h5>
                    <div class="progress">
                        <div class="indeterminate"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-small waves-effect waves-light" id="submitDeleteUser" type="button" name="action">Submit
                <i class="material-icons right">send</i>
            </button>
            <a href="#!" class="modal-close waves-effect waves-green  deep-orange darken-4 btn-small">Cancel</a>
        </div>
    </form>
</div>

<!-- Form for checking file readiness -->
<form action="{{route('folder.fileReadiness')}}" id="fileReadinessForm"></form>

<script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
        $('.modal').modal();

        $('.remove-user').on("click", (function(e) {
            e.preventDefault();
            var user = $(this).attr('href');
            const userData = user.split(', with email: ');
            var id = $(this).attr('id');
            $('input[name=userid]').val(id);
            $('input[name=userName]').val(userData[0]);
            $('.usertoremove').html(userData[0] + ', with email: ' + userData[1]);
        }));

        $('#submitDeleteUser').on("click", (function(e) {
            /*  UI for preparing download */
            e.preventDefault();
            var elem = document.getElementById('preparingBackup');
            var forWhat = document.getElementById('preparing');
            var backup = document.getElementById('backup');
            var form = document.getElementById('deleteform');
            var userName = $('input[name=userName]').val();
            if (backup.checked == true) {
                elem.classList.remove("hide");
                forWhat.innerHTML = "Creating backup of user storage !!! Be patient.";
                form.submit();
                var checkFile = setInterval(function() {
                    $.ajax({
                        url: $('#fileReadinessForm').attr("action"),
                        type: "POST",
                        data: {
                            '_token': $('input[name=_token]').val(),
                            'filePath': '/Backup/' + userName + '.zip',
                        },
                        success: function(data) {
                            if (typeof data.ready !== "undefined") {
                                if (data.ready === true) {
                                    $('input[name="backup"]').prop("checked", false);
                                    clearInterval(checkFile);
                                }
                            }
                        }
                    });
                }, 2000);
            }else{
                form.submit();
            }

        }));



    });
</script>


@endsection