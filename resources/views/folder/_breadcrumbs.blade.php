<div class="row blue-grey">
    <div class="col s2 right-align">
        <!--  Modal directory tree -->
        <a href="#directoryTreeModal" class="left-align modal-trigger tooltipped" data-tooltip="Folder tree"><i class="material-icons medium orange-text">view_list</i></a>
    </div>
    <div class="col s10 left-align white-text">
        <strong>Current folder:</strong>
        @foreach($breadcrumbs as $piece)
        @if ($loop->last)
        <span class="lime-text"><strong>{{$piece['folder']}}</strong></span>
        @else
        <a href="{{route('folder.root', ['current_folder' => $piece['path']])}}">
            <span class="orange-text text-lighten-4"><u>{{$piece['folder']}}</u></span>
        </a>
        &nbsp;<strong>></strong>&nbsp;
        @endif
        @endforeach
    </div>
</div>