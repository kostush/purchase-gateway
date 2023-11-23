<html>
    <head>
        <title>Error</title>
    </head>
    <body>
        <form name="toClient" action="{{ $clientUrl }}" method="POST">
            <input type="hidden" name="error" value='@json($response)'>
        </form>
        <script type="text/javascript">
            window.onload = function () {
                document.forms["toClient"].submit();
            }
        </script>
    </body>
</html>