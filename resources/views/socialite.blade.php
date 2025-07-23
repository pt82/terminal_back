<html>
<head>
    <meta charset="utf-8">
    <title>Callback</title>
    <script>
        window.opener.postMessage({ id: "{{ $id }}", name: "{{ $name }}", data: '{!! $data !!}' }, "*");
        window.close();
    </script>
</head>
<body>
</body>
</html>
