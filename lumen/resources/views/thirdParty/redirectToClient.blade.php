<html>
    <head>
        <title>Secure redirect to client</title>
    </head>
    <body>
        <form name="toClient" action="{{ $clientUrl }}" method="POST">
            <input type="hidden" name="success" value='@json($response)'>
        </form>
        <script type="text/javascript">
            window.onload = function () {
                document.forms["toClient"].submit();
            }
        </script>
    </body>
</html>