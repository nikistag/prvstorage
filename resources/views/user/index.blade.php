@extends('layouts.app', ['title' => 'Users'])

@section('content')

<a href="{{route('folder.root', ['current_folder' => '/'])}}" class="waves-effect waves-light btn-small left"><i class="material-icons left">arrow_back</i>Back home</a>
<h4>Users</h4>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Row</th>
            <th>Email</th>
            <th>Name</th>
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
            <td>{{$user->active_status}}</td>
            <td>{{$user->admin_role}}</td>
            <td>
                <a href="{{ route('user.edit', ['user' => $user->id])}}" class="tooltipped" data-tooltip="Edit"><i class="material-icons green-text">edit</i></a>
                <a id="{{$user->name}}" href="{{$user->name}}" class="modal-trigger remove-user tooltipped" data-target="modalremoveuser" data-tooltip="Delete"><i class="material-icons red-text">remove_circle</i></a>
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>

<!-- Remove file modal -->
<div id="modalremovefile" class="modal">
    <form id="deleteform" method="POST" action="">
        <div class="modal-content">
            <h5 class="red-text">Are you sure to delete this user?</h5>
            @csrf
            <div class="row">
                <div class="col s12">
                    <div class="input-field inline">
                        <span class="usertoremove"></span>
                        <input id="userid" name="userid" type="hidden" class="valid" value="" />
                        <label for="file"></label>
                    </div>
                    <div class="input-field inline">
                        <label>
                            <input id="deletefolders" name="deletefolders" type="checkbox" class="filled-in"  />
                            <span>Filled in</span>
                        </label>
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

        $('.remove-user').on("click", (function(e) {
            e.preventDefault();
            var user = $(this).attr('href');
            var id = $(this).attr('id');
            $('input[name=userid]').val(id);
            $('.usertoremove').html(user);

        }));

    });
</script>


@endsection