@extends('layouts.app', ['title' => 'Edit user'])

@section('content')

<div class="row">
    <form method="POST" action="{{ route('user.update', ['user' => $user->id]) }}">
        @method('PUT')
        @csrf
        <div class="col s12 m6">

            <h5>Edit user</h5>

        </div>
        <div class="row">
            <div class="col s12">
                <div class="input-field inline">
                    <input id="name" name="name" type="text" value="{{$user->name}}" size="40" disabled />
                    <label for="name">Username</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <div class="input-field inline">
                    <input id="email" name="email" type="text" class="{{$errors->has('email') ? 'invalid':'valid'}}" value="{{$user->email}}" size="40" />
                    <label for="email">Email</label>
                    @if ($errors->has('email'))
                    <span class="helper-text invalid" data-error="{{ $errors->first('email') }}">
                        @else
                        <span class="valid" data-success="OK"></span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <div class="input-field inline">
                    <div class="switch">
                        <label>
                            Active
                            @if($user->active == 0)
                            <input type="checkbox" name="active" id="active">
                            @else
                            <input type="checkbox" name="active" id="active" checked>
                            @endif
                            <span class="lever"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        @if(auth()->user()->suadmin == 1)
        <div class="row">
            <div class="col s12">
                <div class="input-field inline">
                    <div class="switch">
                        <label>
                            Admin
                            @if($user->admin == 0)
                            <input type="checkbox" name="admin" id="admin">
                            @else
                            <input type="checkbox" name="admin" id="admin" checked>
                            @endif
                            <span class="lever"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col s12">
                <button class="btn waves-effect waves-light" type="submit" name="action">Submit
                    <i class="material-icons right">send</i>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    
</script>

@endsection