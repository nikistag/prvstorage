<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" value="{{ csrf_token() }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('css/materialize.css') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('img/prvstorage.ico') }}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('css/prvstorage.css') }}" />

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
                <a href="/index.php" class="brand-logo left"><span
                        class="orange-text text-lighten-4"><strong>{{ auth()->user()->name }}'s</strong></span> <span
                        style="font-size:medium;">private storage</span></a>
                <a href="#" data-target="mobile-nav-menu" class="right sidenav-trigger"><i
                        class="material-icons">menu</i></a>
                <ul id="nav-mobile" class="right hide-on-med-and-down">
                    <li>
                        <a href="{{ route('user.view', ['user' => auth()->user()->id]) }}"><span
                                class="white-text">Account</span></a>
                    </li>
                    <li>
                        <a href="{{ route('share.index') }}"><span class="white-text">Outside shares</span></a>
                    </li>
                    <li>
                        <a href="{{ route('ushare.index') }}"><span class="white-text">Local shares</span></a>
                    </li>
                    @if (auth()->user()->admin === 1)
                        <li>
                            <a href="{{ route('user.index') }}"><span class="white-text">Users</span></a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('user.admins') }}"><span class="white-text">Admins</span></a>
                        </li>
                    @endif
                    @if (auth()->user()->suadmin === 1)
                        <li>
                            <a href="{{ route('user.emailTest') }}"><span class="white-text">Email test</span></a>
                        </li>
                    @endif
                    <li>
                        <a id="logoutwide" href="!#"><span
                                class="logouttrigger teal-text text-lighten-4">Logout</span></a>
                    </li>
                    <li>
                        &nbsp;
                    </li>

                </ul>
            @endauth
            @guest
                &nbsp;
                <a href="/index.php" class="brand-logo left "><img src="{{ asset('img/prvstorage_logo2_40_40.png') }}"
                        alt="Prvstorage logo">
                    <span class="hide-on-med-and-down">Private storage</span><span
                        class="hide-on-large-only">Prvstorage</span>
                </a>
                <a href="#" data-target="mobile-nav-menu" class="right sidenav-trigger"><i
                        class="material-icons">menu</i></a>
                <ul id="nav-mobile" class="right hide-on-med-and-down">
                    <li><a href="{{ route('login') }}"><span class="white-text">Login</span></a></li>
                    <li><a href="{{ route('register') }}"><span class="white-text">Register</span></a></li>
                </ul>
            @endguest
    </nav>
    @auth
        <ul class="sidenav blue-grey darken-2 white-text" id="mobile-nav-menu">
            <li>
                <a href="{{ route('user.view', ['user' => auth()->user()->id]) }}"><span
                        class="white-text">Account</span></a>
            </li>
            <li>
                <a href="{{ route('share.index') }}"><span class="white-text">Outside shares</span></a>
            </li>
            <li>
                <a href="{{ route('ushare.index') }}"><span class="white-text">Local shares</span></a>
            </li>
            @if (auth()->user()->admin === 1)
                <li>
                    <a href="{{ route('user.index') }}"><span class="white-text">Users</span></a>
                </li>
            @else
                <li>
                    <a href="{{ route('user.admins') }}"><span class="white-text">Admins</span></a>
                </li>
            @endif
            <li>
                <a id="logoutmobile" href="!#"><span class="logouttrigger teal-text text-lighten-4">Logout</span></a>
            </li>
        </ul>
        <form id="logoutform" method="POST" action="{{ route('logout') }}">
            @csrf
        </form>
    @endauth
    @guest
        <ul class="sidenav blue-grey darken-2" id="mobile-nav-menu">
            <li><a href="{{ route('login') }}"><span class="white-text">Login</span></a></li>
            <li><a href="{{ route('register') }}"><span class="white-text">Register</span></a></li>
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
                        <li><a class="grey-text text-lighten-3" href="https://materializecss.com/">Materializecss</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-copyright blue-grey darken-4">
            <div class="container">
                Copyright &copy; 2021 - {{ date('Y') }} Nichita Sandu / <a href="https://nikistag.com"
                    target="_blank">nikistag.com</a> - All Rights Reserved
                <div id="newVersion" class="right"></div>
                <div class="grey-text text-lighten-4 right">V. <b>{{ config('app.version') }}</b></div>
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
            $('.logouttrigger').on('click', function(e) {
                e.preventDefault();
                $('#logoutform').submit();
            });
            $('.collapsible').collapsible();

            /*  Check for new version/ update  */
            $.ajax({
                url: "https://nikistag.com/api/prvstorage/getversion",
                /* url: "http://192.168.1.35/index.php/api/prvstorage/getversion", */ //Testing URL
                type: "GET",
                data: {
                    'currentVersion': "{{ config('app.version') }}",
                },
                success: function(data) {
                    if (typeof data.newRelease !== "undefined") {
                        if (data.newRelease === true) {
                            document.getElementById('newVersion').innerHTML = data.newVersionHtml;
                            $('.tooltipped').tooltip();
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
