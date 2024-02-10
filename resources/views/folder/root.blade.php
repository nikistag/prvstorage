@extends('layouts.folder', ['title' => 'Root folder'])

@section('content')
<div class="row">
    <div><span>
            <?= $quota ?>
        </span> % of disk space in use.
        <?= $disk_free_space ?> Gb free space
    </div>
    <div class="progress">
        <div class="determinate" style="width:<?= $quota ?>%;"></div>
    </div>
</div>

@include('folder._topActionBar')

@include('folder._sidenavFolderView')

@include('folder._breadcrumbs')

@include('folder._folderView')

@include('folder._fileView')


<!-- Preparing data modal -->
<div id="modalbgworking" class="modal">
    <div class="modal-content">
        <h5 class="indigo-text" id="preparing"></h5>
    </div>
    <div class="modal-footer">
        <div class="progress">
            <div class="indeterminate"></div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.tooltipped').tooltip();
        $('.sidenav-tree').sidenav();
        $('.modal').modal();
        $('.modalupload').modal({
            dismissible: false,
        });
        $('#modalfolderupload').modal({
            dismissible: false,
        });
        $('#modalfileshare').modal({
            dismissible: false,
        });
        $('#modal1').modal({
            onOpenEnd: function () {
                $('#newfolder').focus();
            },
        });
        $('select').formSelect();
        $('#folder-tree-view').sidenav({
            edge: 'left'
        });
        /** Folder tree expanding */
        var toggler = document.getElementsByClassName("folder-tree");
        var i;
        for (i = 0; i < toggler.length; i++) {
            toggler[i].addEventListener("click", function () {
                this.parentElement.querySelector(".nested").classList.toggle("active-tree");
                this.classList.toggle("folder-tree-down");
            });
        }
        /** Share folder tree expanding */
        var toggler = document.getElementsByClassName("folder-tree-ushare");
        var i;
        for (i = 0; i < toggler.length; i++) {
            toggler[i].addEventListener("click", function () {
                this.parentElement.querySelector(".nested-ushare").classList.toggle(
                    "active-tree-ushare");
                this.classList.toggle("folder-tree-ushare-down");
            });
        }

    });

    function jsUpload(form, input, display, drop) {

        var fileCatcher = document.getElementById(form); //form
        var fileInput = document.getElementById(input); //input        
        var fileListDisplay = document.getElementById(display); //display
        var fileList = [];
        var renderFileList, sendFile;

        /* Drag and drop */
        if ((drop == "filesdroparea") || (drop == "drop for folder")) {
            let dropArea = document.getElementById(drop);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false)
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false)
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false)
            });

            function highlight(e) {
                dropArea.classList.add('highlight');
            }

            function unhighlight(e) {
                dropArea.classList.remove('highlight');
            }

            dropArea.addEventListener('drop', handleDrop, false)

            function handleDrop(e) {
                let dt = e.dataTransfer;
                let droppedFiles = dt.files;
                var clientFiles = Array.from(droppedFiles);
                const result = Array.isArray(clientFiles) ? clientFiles.sort(function (a, b) {
                    return a.size - b.size
                }) : [];

                for (var i = 0; i < droppedFiles.length; i++) {
                    fileList.push(clientFiles[i]);
                }
                renderFileList();
            }
        }

        /* Drag and drop end */

        //personal addings
        var multiFileUploadFooter = document.getElementById('multifilefooter');

        fileCatcher.addEventListener('submit', function (evnt) {
            evnt.preventDefault();
            multiFileUploadFooter.classList.add("hide"); //my
            fileList.forEach(function (file) {
                sendFile(file);
            });
        });

        fileInput.addEventListener('change', function (evnt) {
            fileList = [];
            var clientFiles = Array.from(fileInput.files);
            const result = Array.isArray(clientFiles) ? clientFiles.sort(function (a, b) {
                return a.size - b.size
            }) : [];
            console.log(clientFiles);
            for (var i = 0; i < fileInput.files.length; i++) {
                fileList.push(clientFiles[i]);
            }
            renderFileList();
        });

        renderFileList = function () {

            fileListDisplay.innerHTML = '';
            fileList.forEach(function (file) { //added index
                var fileDisplayEl = document.createElement("div");
                fileDisplayEl.setAttribute("class", "collection-item notuploaded");
                fileDisplayEl.setAttribute("id", file.webkitRelativePath + file.name + file.size + "item");
                fileDisplayEl.innerHTML = '';
                fileListDisplay.appendChild(fileDisplayEl);

                var fileDisplayNameNProgress = document.createElement(
                    "div"); // create row for progress bar and info
                fileDisplayNameNProgress.setAttribute("class", "row");
                fileDisplayNameNProgress.innerHTML = '';
                fileDisplayEl.appendChild(fileDisplayNameNProgress);

                var fileDisplayName = document.createElement("div"); // create div for file info
                fileDisplayName.setAttribute("class", "col s9 right-align");
                fileDisplayName.innerHTML = file.name.length > 38 ? file.name.substr(0, 35) + "~..." : file
                    .name;
                fileDisplayNameNProgress.appendChild(fileDisplayName);

                var fileDisplayProgress = document.createElement(
                    "div"); // create div for progress percentage
                fileDisplayProgress.setAttribute("class", "col s3 right-align green-text");
                fileDisplayProgress.setAttribute("id", file.webkitRelativePath + file.name + file.size +
                    "percent");
                fileDisplayProgress.innerHTML = '0%';
                fileDisplayNameNProgress.appendChild(fileDisplayProgress);

                var progressDisplayEl = document.createElement("div"); // create div for progress bar
                progressDisplayEl.setAttribute("class", "progress");
                progressDisplayEl.setAttribute("id", file.webkitRelativePath + file.name + file.size +
                    "progress");
                progressDisplayEl.innerHTML = "";
                fileDisplayEl.appendChild(progressDisplayEl);

                var progressBarEl = document.createElement("div"); // create progress bar div
                progressBarEl.setAttribute("class", "determinate");
                progressBarEl.setAttribute("id", file.webkitRelativePath + file.name + file.size + "bar");
                progressBarEl.setAttribute("style", "width:1%");
                progressBarEl.innerHTML = "";

                progressDisplayEl.appendChild(progressBarEl);


            });
        };
        sendFile = function (file) {
            var formData = new FormData();
            var request = new XMLHttpRequest();

            formData.set('file', file);
            formData.set('_token', $('input[name=_token]').val());
            formData.set('filepath', file.webkitRelativePath);
            formData.set('current_folder', $('input[name=current_folder]').val());

            request.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var progressBar = document.getElementById(file.webkitRelativePath + file.name + file
                        .size + "bar");
                    var progressPercent = document.getElementById(file.webkitRelativePath + file.name + file
                        .size + "percent");
                    var progresspc = Math.round(evt.loaded * 100 / evt.total);
                    var handbreak = "";
                    progressBar.style.width = progresspc + "%";
                    progressPercent.innerHTML = progresspc + "%";
                    if (progresspc == 100) {
                        console.log("Saved one file!");
                        progressPercent.innerHTML = "Processing";
                    }
                }
            }, false);

            request.onloadend = function () {
                if (this.status == 200) {
                    var uploadItem = document.getElementById(file.webkitRelativePath + file.name + file.size +
                        "item");
                    uploadItem.classList.remove("notuploaded");
                    uploadItem.classList.add("hide");
                    var notdone = document.getElementsByClassName("notuploaded").length;
                    if (notdone > 0) {
                        console.log("Files still in queue");
                    } else {
                        window.location.reload(true);
                    }
                } else {
                    console.log("error " + this.status);
                }
            };
            request.open("POST", $("#" + form).attr("action"));
            request.send(formData);
        };
    };
</script>
@endsection