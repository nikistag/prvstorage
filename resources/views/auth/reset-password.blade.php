@extends('layouts.app', ['title' => 'Reset password'])

@section('content')

<div class="row">
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="col s12 m6">

            <h5>Login form</h5>

        </div>
        <div class="row">
            <div class="col s12">
                <div class="input-field inline">
                    <input id="email" name="email" type="text" class="{{$errors->has('email') ? 'invalid':'valid'}}" value="{{old('email')}}" size="40" />
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
                    <input id="password" name="password" type="password" class="{{$errors->has('password') ? 'invalid':'valid'}}" value="" size="40" />
                    <label for="password">Password</label>
                    @if ($errors->has('password'))
                    <span class="helper-text invalid" data-error="{{ $errors->first('password') }}">
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
                    <input id="password_confirmation" name="password_confirmation" type="password" class="{{$errors->has('password_confirmation') ? 'invalid':'valid'}}" value="" size="40" />
                    <label for="password_confirmation">Password confirmation</label>
                    @if ($errors->has('password_confirmation'))
                    <span class="helper-text invalid" data-error="{{ $errors->first('password_confirmation') }}">
                        @else
                        <span class="valid" data-success="OK"></span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <button class="btn waves-effect waves-light" type="submit" name="action">Reset password
                    <i class="material-icons right">send</i>
                </button>
            </div>
        </div>
    </form>
</div>

@endsection