<script>
    $(document).ready(function () {
        /* Check for new version/ update */
        $.ajax({
            url: "https://nikistag.com/api/prvstorage/getversion",
            /* url: "http://192.168.1.145/index.php/api/prvstorage/getversion", *///Testing URL
            type: "GET",
            data: {
                'currentVersion': "{{ config('app.version') }}",
            },
            success: function (data) {
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