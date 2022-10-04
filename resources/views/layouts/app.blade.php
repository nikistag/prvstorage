<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" value="{{ csrf_token() }}">

    <link rel="stylesheet" type="text/css" href={{ asset('css/materialize.css')}} />
    <!-- <link rel="stylesheet" type="text/css" href={{ asset('css/iconfont/material-icons.css')}} /> -->
    <!--  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->


    <script src={{ asset('js/jquery3_6.js') }}></script>
    <script src={{ asset('js/materialize.js') }}></script>

    <title>{!! $title !!}</title>

</head>

<body>

    <!-- Page Content goes here -->
    <nav class="blue-grey darken-2">
        <div class="nav-wrapper ">
            @auth
            &nbsp;
            <a href="/index.php" class="brand-logo left"><span class="orange-text text-lighten-4"><strong>{{ auth()->user()->name }}'s</strong></span> <span style="font-size:medium;">private storage</span></a>
            <a href="#" data-target="mobile-nav-menu" class="right sidenav-trigger"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li>Welcome</li>
                <li>&nbsp;</li>
                <li>
                    <span class="orange-text text-lighten-4">
                        <strong>{{auth()->user()->name}}</strong>
                    </span>
                </li>
                <li>&nbsp;</li>
                <li>
                    <form method="POST" action="{{route('logout')}}">
                        @csrf
                        <button class="btn-small" type="submit">Logout</button>
                    </form>
                </li>
                <li>
                    <a href="{{route('share.index')}}"><span class="white-text">Shares</span></a>
                </li>
                @if(auth()->user()->admin === 1)
                <li>
                    <a href="{{route('user.index')}}"><span class="white-text">Users</span></a>
                </li>
                @else
                <li>
                    <a href="{{route('user.admins')}}"><span class="white-text">Admins</span></a>
                </li>
                @endif
                @if(auth()->user()->suadmin === 1)
                <li>
                    <a href="{{route('user.emailTest')}}"><span class="white-text">Email test</span></a>
                </li>
                @endif
                <li>
                    &nbsp;
                </li>

            </ul>
            @endauth
            @guest
            &nbsp;
            <a href="/index.php" class="brand-logo left">Private storage</a>
            <a href="#" data-target="mobile-nav-menu" class="right sidenav-trigger"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li><a href="{{route('login')}}"><span class="white-text">Login</span></a></li>
                <li><a href="{{route('register')}}"><span class="white-text">Register</span></a></li>
            </ul>
            @endguest
    </nav>
    @auth
    <ul class="sidenav blue-grey darken-2 white-text" id="mobile-nav-menu">
        <li>
            <a id="logouttrigger" href="!#"><span class="white-text">Logout</span></a>
            <form id="logoutform" method="POST" action="{{route('logout')}}">
                @csrf
            </form>
        </li>
        <li>
            <a href="{{route('share.index')}}"><span class="white-text">Shares</span></a>
        </li>
        @if(auth()->user()->admin === 1)
        <li>
            <a href="{{route('user.index')}}"><span class="white-text">Users</span></a>
        </li>
        @else
        <li>
            <a href="{{route('user.admins')}}"><span class="white-text">Admins</span></a>
        </li>
        @endif
    </ul>
    @endauth
    @guest
    <ul class="sidenav blue-grey darken-2" id="mobile-nav-menu">
        <li><a href="{{route('login')}}"><span class="white-text">Login</span></a></li>
        <li><a href="{{route('register')}}"><span class="white-text">Register</span></a></li>
    </ul>
    @endguest
    <div class="container">
        <div class="row blue-grey lighten-5" style="min-height:400px;">
            <div class="col s12 center">
                @include('partials._flash')

                @yield('content')
            </div>
        </div>
    </div>

    <footer class="page-footer blue-grey darken-3">
        <div class="container">
            <div class="row">
                <div class="col l6 s12">
                    <h5 class="white-text">Prvstorage</h5>
                </div>
                <div class="col l4 offset-l2 s12">
                    <h6 class="white-text">Powered by:</h6>
                    <ul>
                        <li><a class="grey-text text-lighten-3" href="https://laravel.com/">Laravel</a></li>
                        <li><a class="grey-text text-lighten-3" href="https://materializecss.com/">Materializecss</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-copyright blue-grey darken-4">
            <div class="container">
                Copyright &copy; 2021 - {{date('Y')}} Nikistag - All Rights Reserved
                <span class="grey-text text-lighten-4 right">V. <b>{{config('app.version')}}</b></span>
            </div>
        </div>
    </footer>
    </div>


    <script>
        $(document).ready(function() {
            //M.AutoInit();
            $('#mobile-nav-menu').sidenav({
                edge: 'right'
            });
            $('#logouttrigger').on('click', function(e) {
                e.preventDefault();
                $('#logoutform').submit();
            });
        });
    </script>

</body>

</html>