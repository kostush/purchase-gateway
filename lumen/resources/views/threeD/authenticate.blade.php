<html>
    <head>
        <title>Secure authentication</title>
    </head>
    <body>
        <form name="toBank" action="{{ $authUrl }}" method="POST">
            <input type="hidden" name="PaReq" value="{{ $pareq }}">
            <input type="hidden" name="TermUrl" value="{{ $returnUrl }}">
        </form>
    <script type="text/javascript">
        window.onload = function () {
            document.forms["toBank"].submit();
        }
    </script>
    </body>
</html>