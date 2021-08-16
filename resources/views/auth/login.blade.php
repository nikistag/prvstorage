@extends('layouts.app', ['title' => 'Login'])

@section('content')

<div class="row">
    <form method="POST" action="{{ route('login') }}">
        @csrf
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
                <label>
                    <input type="checkbox" class="filled-in" />
                    <span>Remember me</span>
                </label>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <div class="input-field inline">
                    <a href="{{route('password.request')}}">Forgot password</a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <button class="btn waves-effect waves-light" type="submit" name="action">Submit
                    <i class="material-icons right">send</i>
                </button>
            </div>
        </div>
    </form>
</div>

@endsection