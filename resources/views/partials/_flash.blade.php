@if ($message = Session::get('success'))
<div class="card-panel  green accent-2" id="flashCard">
    <strong>{{ $message }}</strong>
</div>
@endif


@if ($message = Session::get('error'))
<div class="card-panel pink accent-2" id="flashCard">
    <strong>{{ $message }}</strong>
</div>
@endif


@if ($message = Session::get('warning'))
<div class="card-panel amber darken-1" id="flashCard">
    <strong>{{ $message }}</strong>
</div>
@endif


@if ($message = Session::get('info'))
<div class="card-panel grey lighten-3" id="flashCard">
    <strong>{{ $message }}</strong>
</div>
@endif
<script>
    $(document).ready(function () {
        setTimeout(function () {
            $('#flashCard').fadeOut(6000);
        });
    });
</script>