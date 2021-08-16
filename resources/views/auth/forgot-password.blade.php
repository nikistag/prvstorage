@extends('layouts.app', ['title' => 'Password reset link'])

@section('content')

<div class="row">
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="col s12 m6">

            <h5>Password reset link form</h5>

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
                <button class="btn waves-effect waves-light" type="submit" name="action">Email password reset link
                    <i class="material-icons right">send</i>
                </button>
            </div>
        </div>
    </form>
</div>

@endsection


